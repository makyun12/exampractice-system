<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeAttempt extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['snapshot'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'answers' => 'array', 'started_at' => 'datetime', 'ends_at' => 'datetime', 'submitted_at' => 'datetime', 'score' => 'float'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(PracticePackage::class, 'practice_package_id');
    }

    public function durationSeconds(): int
    {
        return (int) $this->started_at->diffInSeconds($this->submitted_at ?? now());
    }
}
