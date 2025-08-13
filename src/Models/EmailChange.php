<?php

declare(strict_types=1);

namespace MilenMk\LaravelEmailChangeConfirmation\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailChange extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'email_changes';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['user_id', 'current_email', 'new_email', 'change_confirmed_at', 'change_denied_at'];

    /**
     * Get the user that owns the email change.
     */
    public function user(): BelongsTo
    {
        $userModel = config('email-change-confirmation.user_model');

        return $this->belongsTo($userModel, 'user_id');
    }

    /**
     * Determine if the email change has been confirmed.
     */
    public function isConfirmed(): bool
    {
        return ! is_null($this->change_confirmed_at);
    }

    /**
     * Determine if the email change has been denied.
     */
    public function isDenied(): bool
    {
        return ! is_null($this->change_denied_at);
    }

    /**
     * Determine if the email change is still pending.
     */
    public function isPending(): bool
    {
        return ! $this->isConfirmed() && ! $this->isDenied();
    }

    /**
     * Confirm the email change.
     */
    public function confirm(): bool
    {
        $this->change_confirmed_at = now();

        return $this->save();
    }

    /**
     * Deny the email change.
     */
    public function deny(): bool
    {
        $this->change_denied_at = now();

        return $this->save();
    }

    /**
     * Scope to get pending email changes.
     */
    public function scopePending($query)
    {
        return $query->whereNull('change_confirmed_at')->whereNull('change_denied_at');
    }

    /**
     * Scope to get confirmed email changes.
     */
    public function scopeConfirmed($query)
    {
        return $query->whereNotNull('change_confirmed_at');
    }

    /**
     * Scope to get denied email changes.
     */
    public function scopeDenied($query)
    {
        return $query->whereNotNull('change_denied_at');
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'change_confirmed_at' => 'datetime',
            'change_denied_at' => 'datetime',
        ];
    }
}
