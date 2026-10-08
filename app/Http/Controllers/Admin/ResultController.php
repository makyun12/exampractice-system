<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\PracticeAttempt;
use App\Models\PracticePackage;
use App\Models\Program;
use App\Models\User;
use App\Services\AttemptService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultController extends Controller
{
    private function query(Request $request): Builder
    {
        $request->validate(['from' => 'nullable|date', 'to' => $request->filled('from') ? 'nullable|date|after_or_equal:from' : 'nullable|date', 'student' => 'nullable|integer', 'program' => 'nullable|integer', 'module' => 'nullable|integer', 'package' => 'nullable|integer']);
        app(AttemptService::class)->expire();

        return PracticeAttempt::with('user')->whereNotNull('submitted_at')
            ->when($request->filled('student'), fn ($q) => $q->where('user_id', $request->integer('student')))
            ->when($request->filled('program'), fn ($q) => $q->where('snapshot->program_id', $request->integer('program')))
            ->when($request->filled('module'), fn ($q) => $q->where('snapshot->module_id', $request->integer('module')))
            ->when($request->filled('package'), fn ($q) => $q->where('practice_package_id', $request->integer('package')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('submitted_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('submitted_at', '<=', $request->to))->latest('submitted_at');
    }

    public function index(Request $request): View
    {
        $query = $this->query($request);

        return view('admin.results', ['attempts' => $query->paginate(20)->withQueryString(), 'students' => User::where('role', 'student')->get(), 'programs' => Program::all(), 'modules' => Module::all(), 'packages' => PracticePackage::all()]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->query($request);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Student ID', 'Name', 'Program', 'Module', 'Package', 'Score', 'Correct', 'Answered', 'Total', 'Time (seconds)', 'Status', 'Submitted at'], ',', '"', '');
            foreach ($query->cursor() as $a) {
                $row = [$a->user->username, $a->user->name, $a->snapshot['program'], $a->snapshot['module'], $a->snapshot['title'], $a->score, $a->correct_count, $a->answered_count, count($a->snapshot['questions']), $a->durationSeconds(), $a->status, $a->submitted_at->toIso8601String()];
                $row = array_map(fn ($v) => preg_match('/^[=+@\-\t\r\n]/', (string) $v) ? "'".$v : $v, $row);
                fputcsv($out, $row, ',', '"', '');
            }
            fclose($out);
        }, 'exampractice-results-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
