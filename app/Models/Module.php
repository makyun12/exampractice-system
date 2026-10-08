<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = ['program_id', 'title', 'description', 'status'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(PracticePackage::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(LearningMaterial::class);
    }
}
