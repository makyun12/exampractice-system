<?php

namespace App\Http\Controllers;

use App\Models\PracticeAttempt;
use App\Models\PracticePackage;
use App\Services\AttemptService;
use App\Services\LearningAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class PracticeController extends Controller
{
    public function package(Request $request, PracticePackage $package, LearningAccess $access): View
    {
        abort_unless($access->package($request->user(), $package), 404);
        $package->loadCount(['questions' => fn ($q) => $q->where('status', 'active')]);
        $ongoing = $request->user()->attempts()->where('practice_package_id', $package->id)->where('status', 'in_progress')->where('ends_at', '>', now())->first();

        return view('student.package', compact('package', 'ongoing'));
    }

    public function start(Request $request, PracticePackage $package, LearningAccess $access, AttemptService $service): RedirectResponse
    {
        abort_unless($access->package($request->user(), $package), 404);
        $attempt = $service->start($request->user(), $package);

        return redirect()->route('practice.show', $attempt);
    }

    private function authorizeAttempt(Request $request, PracticeAttempt $attempt): void
    {
        abort_unless($attempt->user_id === $request->user()->id, 404);
        if ($attempt->status === 'in_progress') {
            abort_unless($attempt->package && app(LearningAccess::class)->package($request->user(), $attempt->package), 403);
        }
    }

    public function show(Request $request, PracticeAttempt $attempt, AttemptService $service): View|RedirectResponse
    {
        $this->authorizeAttempt($request, $attempt);
        if ($attempt->status === 'in_progress' && $attempt->ends_at->lessThanOrEqualTo(now())) {
            $attempt = $service->finish($attempt);
        }
        if ($attempt->status !== 'in_progress') {
            return redirect()->route('practice.result', $attempt);
        }
        $questions = collect($attempt->snapshot['questions'])->map(fn ($q) => Arr::except($q, ['correct_answer', 'explanation']))->values();

        return view('student.practice', compact('attempt', 'questions'));
    }

    public function answer(Request $request, PracticeAttempt $attempt, AttemptService $service): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $data = $request->validate(['question_id' => 'required|integer', 'answer' => 'required|in:A,B,C,D']);
        $saved = $service->saveAnswer($attempt, $data['question_id'], $data['answer']);

        return response()->json(['status' => $saved->status, 'saved' => $saved->answers[$data['question_id']] ?? null, 'remaining' => max(0, (int) ceil(now()->diffInSeconds($saved->ends_at, false)))]);
    }

    public function status(Request $request, PracticeAttempt $attempt, AttemptService $service): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        if ($attempt->status === 'in_progress' && $attempt->ends_at->lessThanOrEqualTo(now())) {
            $attempt = $service->finish($attempt);
        }

        return response()->json(['status' => $attempt->status, 'remaining' => max(0, (int) ceil(now()->diffInSeconds($attempt->ends_at, false)))]);
    }

    public function submit(Request $request, PracticeAttempt $attempt, AttemptService $service): RedirectResponse|JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $service->finish($attempt);

        return $request->expectsJson() ? response()->json(['url' => route('practice.result', $attempt)]) : redirect()->route('practice.result', $attempt);
    }

    public function result(Request $request, PracticeAttempt $attempt, AttemptService $service): View|RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $attempt->user_id === $request->user()->id, 404);
        if ($attempt->status === 'in_progress') {
            if ($attempt->ends_at->isFuture()) {
                return redirect()->route('practice.show', $attempt);
            }
            $attempt = $service->finish($attempt);
        }

        return view('student.result', compact('attempt'));
    }

    public function history(Request $request, AttemptService $service): View
    {
        $service->expire($request->user()->id);
        $attempts = $request->user()->attempts()->latest()->paginate(15);

        return view('student.history', compact('attempts'));
    }
}
