<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use MilenMk\LaravelEmailChangeConfirmation\Traits\HasEmailChangeConfirmation;

/**
 * Example User model showing how to integrate the email change confirmation package.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasEmailChangeConfirmation;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Example of how to get user's display name for notifications.
     * The package will automatically use this if available.
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }
}