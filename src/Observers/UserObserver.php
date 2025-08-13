<?php

declare(strict_types=1);

namespace MilenMk\LaravelEmailChangeConfirmation\Observers;

use Illuminate\Database\Eloquent\Model;
use MilenMk\LaravelEmailChangeConfirmation\Services\EmailChangeService;

class UserObserver
{
    protected EmailChangeService $emailChangeService;

    public function __construct(EmailChangeService $emailChangeService)
    {
        $this->emailChangeService = $emailChangeService;
    }

    /**
     * Handle the User "updating" event.
     * This is called before the model is saved.
     */
    public function updating(Model $user): void
    {
        // Check if email is being changed
        if ($user->isDirty('email')) {
            $originalEmail = $user->getOriginal('email');
            $newEmail = $user->email;

            // Only process if emails are actually different
            if ($originalEmail !== $newEmail) {
                // Prevent the direct email change
                $user->email = $originalEmail;

                // Request email change confirmation instead
                try {
                    $this->emailChangeService->requestEmailChange($user, $newEmail);
                } catch (\Exception $e) {
                    // Log the error but don't throw to prevent breaking the update
                    \Illuminate\Support\Facades\Log::error('Failed to request email change: ' . $e->getMessage(), [
                        'user_id' => $user->getKey(),
                        'original_email' => $originalEmail,
                        'new_email' => $newEmail,
                    ]);
                }
            }
        }
    }

    /**
     * Handle the User "updated" event.
     * This is called after the model is saved.
     */
    public function updated(Model $user): void
    {
        // This method can be used for additional logic after user update
        // Currently not needed but kept for extensibility
    }

    /**
     * Handle the User "saving" event.
     * Alternative approach - this is called before updating/creating.
     */
    public function saving(Model $user): void
    {
        // We use updating() instead to be more specific
        // This method is kept for potential future use
    }
}