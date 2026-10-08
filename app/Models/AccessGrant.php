<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessGrant extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'resource_type', 'resource_id'];
}
