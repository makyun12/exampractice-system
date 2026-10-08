<?php

namespace Database\Seeders;

use App\Models\PracticePackage;
use App\Models\Program;
use App\Models\User;
use App\Services\AttemptService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo data may only be created in local/testing environments.');
        }
        if (User::where('username', 'admin')->exists()) {
            return;
        }
        DB::transaction(function () {
            User::create(['name' => 'ExamPractice Admin', 'username' => 'admin', 'email' => 'admin@example.invalid', 'password' => 'AdminDemo2026!', 'role' => 'admin', 'active' => true, 'locale' => 'en']);
            $student = User::create(['name' => 'Aiko Tanaka', 'username' => 'STU-001', 'email' => 'aiko@example.invalid', 'password' => 'StudentDemo2026!', 'role' => 'student', 'active' => true, 'locale' => 'en']);
            $limited = User::create(['name' => 'Rina Putri', 'username' => 'STU-002', 'email' => 'rina@example.invalid', 'password' => 'StudentDemo2026!', 'role' => 'student', 'active' => true, 'locale' => 'id', 'device_lock' => true]);
            $this->call(LearningContentSeeder::class);
            $japanese = Program::where('cover', 'japanese')->firstOrFail();
            $english = Program::where('cover', 'english')->firstOrFail();
            $vocabulary = $japanese->modules()->firstOrFail();
            [$p1, $p2, $p3, $p4] = PracticePackage::orderBy('id')->take(4)->get()->all();
            $student->grants()->createMany([['resource_type' => 'program', 'resource_id' => $japanese->id], ['resource_type' => 'program', 'resource_id' => $english->id]]);
            $limited->grants()->createMany([['resource_type' => 'package', 'resource_id' => $p1->id], ['resource_type' => 'material', 'resource_id' => $vocabulary->materials()->first()->id]]);
            $service = app(AttemptService::class);
            foreach ([$p1, $p4, $p2, $p1, $p3, $p4] as $i => $package) {
                $attempt = $service->start($student, $package);
                $answers = [];
                foreach ($attempt->snapshot['questions'] as $j => $q) {
                    if ($j < count($attempt->snapshot['questions']) - ($i % 3)) {
                        $answers[$q['id']] = $j % 5 === 4 ? ($q['correct_answer'] === 'A' ? 'B' : 'A') : $q['correct_answer'];
                    }
                }
                $attempt->update(['answers' => $answers]);
                $attempt = $service->finish($attempt);
                $start = now()->subDays(8 - $i)->setTime(9 + $i, 15);
                $attempt->update(['started_at' => $start, 'ends_at' => $start->copy()->addMinutes($package->duration_minutes), 'submitted_at' => $start->copy()->addMinutes(min(6 + $i, $package->duration_minutes - 1)), 'created_at' => $start]);
            }
            DB::table('material_completions')->insert(['user_id' => $student->id, 'learning_material_id' => $vocabulary->materials()->first()->id, 'completed_at' => now()->subDays(3)]);
        });
    }
}
