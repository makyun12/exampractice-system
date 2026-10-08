<?php

namespace App\Services;

use App\Models\PracticeAttempt;
use App\Models\PracticePackage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AttemptService
{
    public function start(User $user, PracticePackage $package): PracticeAttempt
    {
        return DB::transaction(function () use ($user, $package) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = $user->attempts()->where('practice_package_id', $package->id)->where('status', 'in_progress')->first();
            if ($existing && $existing->ends_at->isFuture()) {
                return $existing;
            }
            if ($existing) {
                $this->finish($existing);
            }
            $questions = $package->questions()->where('status', 'active')->orderBy('id')->get();
            abort_if($questions->isEmpty(), 422, __('ui.no_questions'));

            return $user->attempts()->create([
                'practice_package_id' => $package->id,
                'snapshot' => [
                    'title' => $package->title, 'module' => $package->module->title,
                    'program' => $package->module->program->title,
                    'program_id' => $package->module->program_id, 'module_id' => $package->module_id,
                    'passing_score' => $package->passing_score,
                    'show_explanations' => $package->show_explanations,
                    'copy_protection' => $package->copy_protection,
                    'questions' => $questions->map(fn ($q) => $q->only(['id', 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'explanation']))->all(),
                ],
                'answers' => [], 'started_at' => now(), 'ends_at' => now()->addMinutes($package->duration_minutes),
            ]);
        });
    }

    public function saveAnswer(PracticeAttempt $attempt, int $questionId, string $answer): PracticeAttempt
    {
        return DB::transaction(function () use ($attempt, $questionId, $answer) {
            $locked = PracticeAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'in_progress') {
                return $locked;
            }
            if ($locked->ends_at->lessThanOrEqualTo(now())) {
                return $this->finish($locked);
            }
            abort_unless(collect($locked->snapshot['questions'])->contains('id', $questionId), 422);
            $answers = $locked->answers;
            $answers[$questionId] = $answer;
            $locked->update(['answers' => $answers]);

            return $locked;
        });
    }

    public function finish(PracticeAttempt $attempt): PracticeAttempt
    {
        return DB::transaction(function () use ($attempt) {
            $locked = PracticeAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'in_progress') {
                return $locked;
            }
            $questions = collect($locked->snapshot['questions']);
            $correct = $questions->filter(fn ($q) => ($locked->answers[$q['id']] ?? null) === $q['correct_answer'])->count();
            $expired = $locked->ends_at->lessThanOrEqualTo(now());
            $locked->update([
                'status' => $expired ? 'expired' : 'completed',
                'submitted_at' => $expired ? $locked->ends_at : now(),
                'correct_count' => $correct, 'answered_count' => count($locked->answers),
                'score' => round($correct / max(1, $questions->count()) * 100, 2),
            ]);

            return $locked;
        });
    }

    public function expire(?int $userId = null): void
    {
        PracticeAttempt::where('status', 'in_progress')->where('ends_at', '<=', now())
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->eachById(fn ($attempt) => $this->finish($attempt));
    }
}
