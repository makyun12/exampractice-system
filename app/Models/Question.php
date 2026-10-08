<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    protected $fillable = ['practice_package_id', 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'explanation', 'status'];

    public function package(): BelongsTo
    {
        return $this->belongsTo(PracticePackage::class, 'practice_package_id');
    }
}
