<?php

namespace MilenMk\LaravelEmailChangeConfirmation\Tests;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use MilenMk\LaravelEmailChangeConfirmation\Traits\HasEmailChangeConfirmation;

class TestUser extends Authenticatable implements MustVerifyEmail
{
    use Notifiable, HasEmailChangeConfirmation;

    protected $table = 'users';
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }
}