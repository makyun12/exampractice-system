<?php

namespace App\Http\Controllers;

use App\Models\PracticeAttempt;
use App\Models\Program;
use App\Models\Question;
use App\Models\User;
use App\Services\AttemptService;
use App\Services\LearningAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, LearningAccess $access, AttemptService $attempts): View
    {
        $user = $request->user();
        $attempts->expire($user->isAdmin() ? null : $user->id);
        if ($user->isAdmin()) {
            return view('admin.dashboard', [
                'students' => User::where('role', 'student')->count(),
                'programs' => Program::withCount('modules')->get(),
                'questions' => Question::count(),
                'completed' => PracticeAttempt::whereNotNull('submitted_at')->count(),
                'average' => round(PracticeAttempt::whereNotNull('submitted_at')->avg('score') ?? 0),
                'recent' => PracticeAttempt::with('user')->whereNotNull('submitted_at')->latest()->limit(6)->get(),
            ]);
        }
        $packages = $access->packages($user);
        $materials = $access->materials($user);
        $programs = $packages->map(fn ($p) => $p->module->program)->merge($materials->map(fn ($m) => $m->module->program))->unique('id');
        $history = $user->attempts()->whereNotNull('submitted_at')->latest()->get();

        return view('student.dashboard', compact('packages', 'materials', 'programs', 'history') + [
            'recent' => $history->take(4),
            'ongoing' => $user->attempts()->where('status', 'in_progress')->whereIn('practice_package_id', $packages->pluck('id'))->latest()->get(),
        ]);
    }
}
