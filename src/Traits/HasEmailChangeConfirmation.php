<?php

declare(strict_types=1);

namespace MilenMk\LaravelEmailChangeConfirmation\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange;

trait HasEmailChangeConfirmation
{
    /**
     * Get all email changes for the user.
     */
    public function emailChanges(): HasMany
    {
        $emailChangeModel = config('email-change-confirmation.email_change_model');

        return $this->hasMany($emailChangeModel, 'user_id');
    }

    /**
     * Get the latest email change for the user.
     */
    public function latestEmailChange(): HasOne
    {
        $emailChangeModel = config('email-change-confirmation.email_change_model');

        return $this->hasOne($emailChangeModel, 'user_id')->latestOfMany();
    }

    /**
     * Get pending email changes for the user.
     */
    public function pendingEmailChanges(): HasMany
    {
        return $this->emailChanges()->pending();
    }

    /**
     * Check if the user has any pending email changes.
     */
    public function hasPendingEmailChange(): bool
    {
        return $this->pendingEmailChanges()->exists();
    }

    /**
     * Get the latest pending email change.
     */
    public function getLatestPendingEmailChange(): ?EmailChange
    {
        return $this->pendingEmailChanges()
            ->latest()
            ->first();
    }

    /**
     * Check if the user can request an email change.
     */
    public function canRequestEmailChange(): bool
    {
        $maxPending = config('email-change-confirmation.max_pending_changes_per_user', 1);

        return $this->pendingEmailChanges()->count() < $maxPending;
    }

    /**
     * Get the email address for verification purposes.
     * This method ensures compatibility with Laravel's email verification.
     */
    public function getEmailForVerification(): string
    {
        // If there's a pending email change, use the current email for verification
        if ($this->hasPendingEmailChange()) {
            $pendingChange = $this->getLatestPendingEmailChange();

            return $pendingChange ? $pendingChange->current_email : $this->email;
        }

        return $this->email;
    }
}
