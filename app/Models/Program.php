<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    protected $fillable = ['title', 'description', 'cover', 'status'];

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }
}
