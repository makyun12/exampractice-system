<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'username', 'password', 'role', 'active', 'locale', 'device_lock', 'device_hash', 'session_version'];

    protected $hidden = ['password', 'remember_token', 'device_hash'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'active' => 'boolean', 'device_lock' => 'boolean', 'email_verified_at' => 'datetime'];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function grants(): HasMany
    {
        return $this->hasMany(AccessGrant::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PracticeAttempt::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(DeviceRecord::class);
    }
}
