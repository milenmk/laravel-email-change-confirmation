<?php

declare(strict_types=1);

namespace MilenMk\LaravelEmailChangeConfirmation\Observers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
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
                    // First validate the email change
                    $this->emailChangeService->validateEmailChange($user, $newEmail);

                    // If validation passes, request the email change
                    $this->emailChangeService->requestEmailChange($user, $newEmail);
                } catch (Exception $e) {
                    // Check if this is a validation error (user-friendly message)
                    $validationErrors = [
                        'The new email address must be different',
                        'You already have',
                        'This email domain is not allowed',
                        'You have reached the maximum',
                    ];

                    $isValidationError = false;
                    foreach ($validationErrors as $pattern) {
                        if (strpos($e->getMessage(), $pattern) !== false) {
                            $isValidationError = true;
                            break;
                        }
                    }

                    if ($isValidationError) {
                        // Flash user-friendly validation error to session
                        session()->flash('error', $e->getMessage());
                    } else {
                        // Log system errors but don't expose them to users
                        Log::error('Failed to request email change: ' . $e->getMessage(), [
                            'user_id' => $user->getKey(),
                            'original_email' => $originalEmail,
                            'new_email' => $newEmail,
                        ]);

                        // Flash generic error message
                        session()->flash(
                            'error',
                            'Unable to process email change request. Please try again or contact support.',
                        );
                    }
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
