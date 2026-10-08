<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PracticePackage extends Model
{
    protected $fillable = ['module_id', 'title', 'description', 'duration_minutes', 'passing_score', 'show_explanations', 'copy_protection', 'status'];

    protected function casts(): array
    {
        return ['show_explanations' => 'boolean', 'copy_protection' => 'boolean'];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PracticeAttempt::class);
    }
}
