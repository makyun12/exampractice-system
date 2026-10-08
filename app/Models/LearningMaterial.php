<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningMaterial extends Model
{
    protected $fillable = ['module_id', 'title', 'content', 'reading_minutes', 'status'];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
