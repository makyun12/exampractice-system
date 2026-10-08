<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ContentController;
use App\Models\LearningMaterial;
use App\Models\Module;
use App\Models\PracticePackage;
use App\Models\Program;
use App\Models\Question;
use App\Models\User;
use App\Services\AttemptService;
use App\Services\QuestionImport;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ExamPracticeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    private function signIn(string $username = 'STU-001'): User
    {
        $user = User::where('username', $username)->firstOrFail();
        $this->actingAs($user)->withSession(['session_version' => $user->session_version]);

        return $user;
    }

    public function test_login_uses_admin_issued_id_and_hashed_password(): void
    {
        $this->get('/login')->assertOk();
        $this->post('/login', ['username' => 'STU-001', 'password' => 'wrong'])->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->post('/login', ['username' => 'STU-001', 'password' => 'StudentDemo2026!'])->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertTrue(Hash::check('StudentDemo2026!', User::where('username', 'STU-001')->first()->password));
    }

    public function test_disabled_students_cannot_login_and_existing_sessions_are_revoked(): void
    {
        $user = $this->signIn();
        $user->update(['active' => false]);
        $this->get('/dashboard')->assertRedirect('/login');
        $this->post('/login', ['username' => 'STU-001', 'password' => 'StudentDemo2026!'])->assertSessionHasErrors('username');
    }

    public function test_student_cannot_open_admin_routes_or_unassigned_program_content(): void
    {
        $this->signIn();
        $this->get('/admin/students')->assertForbidden();
        $this->get('/admin/content/questions')->assertForbidden();
        $this->get('/programs/3')->assertNotFound();
        $this->get('/packages/5')->assertNotFound();
        $this->post('/packages/5/start')->assertNotFound();
        $this->get('/materials/5')->assertNotFound();
        $this->get('/dashboard')->assertOk()->assertSee('Japanese Language Practice')->assertDontSee('Professional Skills');
    }

    public function test_leaf_grants_only_expose_selected_package_and_material(): void
    {
        $user = User::where('username', 'STU-002')->first();
        $user->update(['device_lock' => false]);
        $this->signIn('STU-002');
        $this->get('/programs/1')->assertOk()->assertSee('N5 Vocabulary')->assertDontSee('N5 Grammar');
        $this->get('/packages/1')->assertOk();
        $this->get('/packages/2')->assertNotFound();
        $this->get('/materials/1')->assertOk();
        $this->get('/materials/2')->assertNotFound();
    }

    public function test_draft_ancestor_hides_descendant_even_with_direct_grant(): void
    {
        $user = $this->signIn();
        $user->grants()->create(['resource_type' => 'package', 'resource_id' => 1]);
        Program::find(1)->update(['status' => 'draft']);
        $this->get('/packages/1')->assertNotFound();
        $this->get('/materials/1')->assertNotFound();
    }

    public function test_repeated_start_resumes_same_attempt_and_refresh_does_not_reset_deadline(): void
    {
        $user = $this->signIn();
        $this->post('/packages/1/start')->assertRedirect();
        $attempt = $user->attempts()->where('status', 'in_progress')->firstOrFail();
        $deadline = $attempt->ends_at->toIso8601String();
        $this->travel(2)->minutes();
        $this->post('/packages/1/start')->assertRedirect(route('practice.show', $attempt));
        $this->get(route('practice.show', $attempt))->assertOk()->assertDontSee('correct_answer')->assertDontSee('means thank you');
        $this->assertSame($deadline, $attempt->fresh()->ends_at->toIso8601String());
        $this->assertSame(1, $user->attempts()->where('status', 'in_progress')->count());
    }

    public function test_scoring_uses_snapshot_and_submission_is_idempotent(): void
    {
        $user = $this->signIn();
        $attempt = app(AttemptService::class)->start($user, PracticePackage::find(1));
        $question = $attempt->snapshot['questions'][0];
        $this->postJson(route('practice.answer', $attempt), ['question_id' => $question['id'], 'answer' => $question['correct_answer']])->assertOk()->assertJsonPath('saved', 'A');
        Question::find($question['id'])->update(['correct_answer' => 'B', 'question' => 'Edited question']);
        $this->postJson(route('practice.submit', $attempt))->assertOk();
        $attempt->refresh();
        $this->assertSame(1, $attempt->correct_count);
        $this->assertEquals(8.33, $attempt->score);
        $this->assertNotSame('Edited question', $attempt->snapshot['questions'][0]['question']);
        $this->postJson(route('practice.answer', $attempt), ['question_id' => $question['id'], 'answer' => 'B'])->assertOk()->assertJsonPath('status', 'completed');
        $this->postJson(route('practice.submit', $attempt))->assertOk();
        $this->assertSame('A', $attempt->fresh()->answers[$question['id']]);
    }

    public function test_deadline_rejects_late_answers_and_marks_unanswered_as_zero(): void
    {
        $user = $this->signIn();
        $attempt = app(AttemptService::class)->start($user, PracticePackage::find(1));
        $this->travel(16)->minutes();
        $this->postJson(route('practice.answer', $attempt), ['question_id' => $attempt->snapshot['questions'][0]['id'], 'answer' => 'A'])->assertOk()->assertJsonPath('status', 'expired');
        $attempt->refresh();
        $this->assertSame(0, $attempt->answered_count);
        $this->assertEquals(0, $attempt->score);
        $this->assertTrue($attempt->submitted_at->equalTo($attempt->ends_at));
    }

    public function test_expiry_command_finalizes_closed_browser_sessions(): void
    {
        $user = $this->signIn();
        $attempt = app(AttemptService::class)->start($user, PracticePackage::find(1));
        $this->travel(16)->minutes();
        $this->artisan('practice:expire')->assertSuccessful();
        $this->assertSame('expired', $attempt->fresh()->status);
    }

    public function test_other_students_cannot_read_or_modify_attempts(): void
    {
        $owner = User::where('username', 'STU-001')->first();
        $attempt = app(AttemptService::class)->start($owner, PracticePackage::find(1));
        User::where('username', 'STU-002')->update(['device_lock' => false]);
        $this->signIn('STU-002');
        $this->get(route('practice.show', $attempt))->assertNotFound();
        $this->postJson(route('practice.answer', $attempt), ['question_id' => 1, 'answer' => 'A'])->assertNotFound();
        $this->post(route('practice.submit', $attempt))->assertNotFound();
        $this->get(route('practice.result', $attempt))->assertNotFound();
    }

    public function test_answer_validation_prevents_foreign_questions_and_invalid_options(): void
    {
        $user = $this->signIn();
        $attempt = app(AttemptService::class)->start($user, PracticePackage::find(1));
        $this->postJson(route('practice.answer', $attempt), ['question_id' => 9999, 'answer' => 'A'])->assertUnprocessable();
        $this->postJson(route('practice.answer', $attempt), ['question_id' => 1, 'answer' => 'E'])->assertUnprocessable();
        $this->assertSame([], $attempt->fresh()->answers);
    }

    public function test_admin_can_create_edit_and_archive_all_content_types(): void
    {
        $this->signIn('admin');
        $cases = [
            ['programs', Program::class, ['title' => 'Test program', 'description' => 'Description', 'cover' => 'english', 'status' => 'active']],
            ['modules', Module::class, ['title' => 'Test module', 'program_id' => 1, 'status' => 'active']],
            ['packages', PracticePackage::class, ['title' => 'Test package', 'module_id' => 1, 'duration_minutes' => 20, 'passing_score' => 70, 'show_explanations' => 1, 'copy_protection' => 0, 'status' => 'active']],
            ['questions', Question::class, ['practice_package_id' => 1, 'question' => 'Test question?', 'option_a' => 'One', 'option_b' => 'Two', 'option_c' => 'Three', 'option_d' => 'Four', 'correct_answer' => 'B', 'status' => 'active']],
            ['materials', LearningMaterial::class, ['title' => 'Test material', 'module_id' => 1, 'content' => '## Example', 'reading_minutes' => 5, 'status' => 'active']],
        ];
        foreach ($cases as [$kind,$model,$data]) {
            $this->post(route('admin.content.store', $kind), $data)->assertSessionHasNoErrors()->assertRedirect();
            $item = $model::latest('id')->first();
            $this->get(route('admin.content.edit', [$kind, $item->id]))->assertOk();
            $data['status'] = 'draft';
            $this->put(route('admin.content.update', [$kind, $item->id]), $data)->assertSessionHasNoErrors();
            $this->assertSame('draft', $item->fresh()->status);
            $this->delete(route('admin.content.archive', [$kind, $item->id]))->assertRedirect();
            $this->assertSame('archived', $item->fresh()->status);
        }
    }

    public function test_material_html_and_unsafe_links_are_sanitized_and_completion_is_unique(): void
    {
        $user = $this->signIn();
        $material = LearningMaterial::find(1);
        $material->update(['content' => '<script>alert("xss")</script> [unsafe](javascript:alert(1))']);
        $this->get(route('materials.show', $material))->assertOk()->assertDontSee('<script>alert', false)->assertDontSee('href="javascript:', false);
        $this->post(route('materials.complete', $material))->assertRedirect();
        $this->post(route('materials.complete', $material))->assertRedirect();
        $this->assertDatabaseCount('material_completions', 1);
    }

    public function test_import_preview_is_non_mutating_and_confirm_is_atomic_and_idempotent(): void
    {
        $this->signIn('admin');
        $count = Question::count();
        $file = UploadedFile::fake()->createWithContent('questions.csv', implode(',', QuestionImport::HEADERS)."\nImported question?,One,Two,Three,Four,A,Explanation,active\n");
        $response = $this->post(route('admin.import.preview'), ['package_id' => 1, 'file' => $file])->assertOk();
        $this->assertSame($count, Question::count());
        $token = $response->viewData('token');
        $this->post(route('admin.import.confirm'), ['token' => $token])->assertRedirect();
        $this->assertSame($count + 1, Question::count());
        $this->post(route('admin.import.confirm'), ['token' => $token])->assertSessionHasErrors('file');
        $this->assertSame($count + 1, Question::count());
    }

    public function test_invalid_import_does_not_allow_partial_commit(): void
    {
        $this->signIn('admin');
        $count = Question::count();
        $file = UploadedFile::fake()->createWithContent('questions.csv', implode(',', QuestionImport::HEADERS)."\nValid unique?,One,Two,Three,Four,A,Explanation,active\nInvalid?,One,Two,Three,Four,Z,Explanation,active\n");
        $response = $this->post(route('admin.import.preview'), ['package_id' => 1, 'file' => $file])->assertOk();
        $this->assertCount(1, $response->viewData('preview')['errors']);
        $this->post(route('admin.import.confirm'), ['token' => $response->viewData('token')])->assertSessionHasErrors('file');
        $this->assertSame($count, Question::count());
    }

    public function test_excel_import_and_both_templates_work(): void
    {
        $this->signIn('admin');
        $this->get(route('admin.import.template', 'csv'))->assertOk()->assertDownload();
        $this->get(route('admin.import.template', 'xlsx'))->assertOk()->assertDownload();
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([QuestionImport::HEADERS, ['Excel question?', 'One', 'Two', 'Three', 'Four', 'B', 'Explanation', 'draft']]);
        ob_start();
        (new Xlsx($book))->save('php://output');
        $bytes = ob_get_clean();
        $file = UploadedFile::fake()->createWithContent('questions.xlsx', $bytes);
        $response = $this->post(route('admin.import.preview'), ['package_id' => 1, 'file' => $file])->assertOk();
        $this->assertCount(1, $response->viewData('preview')['rows']);
        $this->assertCount(0, $response->viewData('preview')['errors']);
    }

    public function test_admin_can_create_student_assign_access_and_reset_password(): void
    {
        $this->signIn('admin');
        $data = ['name' => 'New Student', 'username' => 'STU-003', 'password' => 'SecurePass2026', 'active' => 1, 'device_lock' => 0, 'locale' => 'ja'];
        $this->post(route('admin.students.store'), $data)->assertSessionHasNoErrors();
        $student = User::where('username', 'STU-003')->firstOrFail();
        $this->put(route('admin.students.access', $student), ['grants' => ['package:1', 'material:1']])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2, $student->grants()->count());
        $data['password'] = 'NewSecurePass2026';
        $this->put(route('admin.students.update', $student), $data)->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewSecurePass2026', $student->fresh()->password));
        $this->assertSame(2, $student->fresh()->session_version);
    }

    public function test_bound_device_rejects_other_browser_and_reset_revokes_old_session(): void
    {
        $student = User::where('username', 'STU-002')->first();
        $student->update(['device_hash' => hash('sha256', 'device-a')]);
        $this->withCookie('ep_device', 'device-b')->post('/login', ['username' => 'STU-002', 'password' => 'StudentDemo2026!'])->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->withCookie('ep_device', 'device-a')->post('/login', ['username' => 'STU-002', 'password' => 'StudentDemo2026!'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($student);
        $this->signIn('admin');
        $this->post(route('admin.students.reset-device', $student))->assertRedirect();
        $this->assertNull($student->fresh()->device_hash);
        $this->assertSame(2, $student->fresh()->session_version);
        $this->actingAs($student->fresh())->withSession(['session_version' => 1]);
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_result_filters_and_export_only_include_selected_student(): void
    {
        $this->signIn('admin');
        $this->get('/admin/results?student=3')->assertOk()->assertDontSee('Aiko Tanaka</strong>', false);
        $response = $this->get('/admin/results/export?student=3')->assertOk()->assertDownload();
        $this->assertStringNotContainsString('STU-001', $response->streamedContent());
        $this->get('/admin/results?program=1&module=1&package=1')->assertOk();
        $this->get('/admin/results?to=2026-12-31')->assertOk();
    }

    public function test_all_interface_languages_have_identical_translation_keys(): void
    {
        $en = require lang_path('en/ui.php');
        foreach (['id', 'ja'] as $locale) {
            $other = require lang_path($locale.'/ui.php');
            $this->assertSame([], array_diff(array_keys($en), array_keys($other)));
            $this->assertSame([], array_diff(array_keys($other), array_keys($en)));
        }
        $this->signIn();
        $this->post('/locale', ['locale' => 'ja'])->assertRedirect();
        $this->get('/dashboard')->assertOk()->assertSee('ダッシュボード');
    }

    public function test_all_admin_and_student_pages_render(): void
    {
        $this->signIn('admin');
        foreach (['/dashboard', '/admin/students', '/admin/students/create', '/admin/students/2/edit', '/admin/devices', '/admin/results', '/admin/import', '/profile'] as $path) {
            $this->get($path)->assertOk();
        }
        foreach (array_keys(ContentController::MODELS) as $kind) {
            $this->get('/admin/content/'.$kind)->assertOk();
            $this->get('/admin/content/'.$kind.'/create')->assertOk();
        }
        $this->signIn();
        foreach (['/dashboard', '/programs', '/programs/1', '/materials', '/materials/1', '/packages/1', '/history', '/profile', '/results/1'] as $path) {
            $this->get($path)->assertOk();
        }
    }
}
