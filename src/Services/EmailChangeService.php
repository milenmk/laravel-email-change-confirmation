<?php

declare(strict_types=1);

namespace MilenMk\LaravelEmailChangeConfirmation\Services;

use Exception;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Livewire\Component;
use MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange;
use MilenMk\LaravelEmailChangeConfirmation\Notifications\EmailChangeCancelled;

class EmailChangeService
{
    /**
     * Request an email change for the given user.
     */
    public function requestEmailChange(Model $user, string $newEmail): EmailChange
    {
        // Check if user can request email change
        if (method_exists($user, 'canRequestEmailChange') && ! $user->canRequestEmailChange()) {
            throw new Exception('User has reached the maximum number of pending email changes.');
        }

        // Create the email change record
        $emailChangeModel = config('email-change-confirmation.email_change_model');
        $emailChange = new $emailChangeModel([
            'user_id' => $user->getKey(),
            'current_email' => $user->email,
            'new_email' => $newEmail,
        ]);
        $emailChange->save();

        // Send confirmation email to current email address
        $this->sendConfirmationEmail($user, $emailChange);

        // Send notification to user (for UI feedback)
        $this->sendUserNotification($user);

        return $emailChange;
    }

    /**
     * Confirm an email change.
     */
    public function confirmEmailChange(EmailChange $emailChange): bool
    {
        if (! $emailChange->isPending()) {
            return false;
        }

        $user = $emailChange->user;
        $oldEmail = $user->email;

        // Update user email using updateQuietly to bypass model events (including our observer)
        // This prevents the observer from interfering with the confirmation process
        $user->updateQuietly(['email' => $emailChange->new_email]);

        // Mark email change as confirmed
        $emailChange->confirm();

        // Reset email verification if user implements MustVerifyEmail
        if ($user instanceof MustVerifyEmail) {
            // Use updateQuietly to avoid mass assignment issues
            $user->updateQuietly(['email_verified_at' => null]);
        }

        // Send email verification if enabled and user implements MustVerifyEmail
        if (
            config('email-change-confirmation.auto_send_email_verification', true) &&
            $user instanceof MustVerifyEmail
        ) {
            $user->sendEmailVerificationNotification();
        }

        return true;
    }

    /**
     * Deny an email change.
     */
    public function denyEmailChange(EmailChange $emailChange): bool
    {
        if (! $emailChange->isPending()) {
            return false;
        }

        return $emailChange->deny();
    }

    /**
     * Get pending email changes for a user.
     */
    public function getPendingEmailChanges(Model $user)
    {
        $emailChangeModel = config('email-change-confirmation.email_change_model');

        return $emailChangeModel::where('user_id', $user->getKey())
            ->pending()
            ->get();
    }

    /**
     * Cancel all pending email changes for a user.
     */
    public function cancelPendingEmailChanges(Model $user): int
    {
        $emailChangeModel = config('email-change-confirmation.email_change_model');

        // Get pending changes before cancelling to send notification
        $pendingChanges = $emailChangeModel::where('user_id', $user->getKey())
            ->pending()
            ->get();

        if ($pendingChanges->isEmpty()) {
            return 0;
        }

        // Cancel the changes by marking them as denied
        $cancelled = $emailChangeModel::where('user_id', $user->getKey())
            ->pending()
            ->update(['change_denied_at' => now()]);

        // Send notification about cancellation
        if ($cancelled > 0 && $this->canSendNotification($user)) {
            $this->sendCancellationNotification($user, $pendingChanges);
        }

        return $cancelled;
    }

    /**
     * Check if an email change is valid and can be processed.
     * Returns true if valid, throws exception with specific error message if not.
     */
    public function validateEmailChange(Model $user, string $newEmail): bool
    {
        // Check if new email is different from current
        if ($user->email === $newEmail) {
            throw new Exception('The new email address must be different from your current email address.');
        }

        // Check if user can request email change
        if (method_exists($user, 'canRequestEmailChange') && ! $user->canRequestEmailChange()) {
            $maxPending = config('email-change-confirmation.max_pending_changes_per_user', 1);
            throw new Exception(
                "You already have {$maxPending} pending email change request(s). Please wait for them to be processed or cancel them before requesting a new one.",
            );
        }

        // Check against blocked domains
        if (! $this->isEmailDomainAllowed($newEmail)) {
            throw new Exception('This email domain is not allowed. Please use a different email address.');
        }

        // Check for recent requests (rate limiting)
        if (! $this->isWithinRateLimit($user)) {
            $maxRequests = config('email-change-confirmation.max_requests_per_hour', 5);
            throw new Exception(
                "You have reached the maximum of {$maxRequests} email change requests per hour. Please try again later.",
            );
        }

        return true;
    }

    /**
     * Send confirmation email to the user's current email address.
     */
    protected function sendConfirmationEmail(Model $user, EmailChange $emailChange): void
    {
        // Check if user has Notifiable trait or can receive notifications
        if (! $this->canSendNotification($user)) {
            throw new Exception('User model must use the Notifiable trait to receive email change confirmations.');
        }

        $notificationClass = config('email-change-confirmation.email_change_notification');
        $user->notify(new $notificationClass($emailChange));
    }

    /**
     * Check if the user can receive notifications.
     */
    protected function canSendNotification(Model $user): bool
    {
        // Check if user uses Notifiable trait
        $traits = class_uses_recursive(get_class($user));

        return in_array(Notifiable::class, $traits) || method_exists($user, 'notify');
    }

    /**
     * Send notification to user about the email change request.
     */
    protected function sendUserNotification(Model $user): void
    {
        if (! config('email-change-confirmation.send_notification_to_user', true)) {
            return;
        }

        $message = config('email-change-confirmation.notification_message');

        // For Livewire applications
        if (config('email-change-confirmation.livewire_enabled', false)) {
            $eventName = config('email-change-confirmation.livewire_notification_event');

            // Dispatch browser event if in Livewire context
            if (class_exists(Component::class) && app()->bound('livewire')) {
                try {
                    $component = app('livewire')->current();
                    if ($component) {
                        $component->dispatch($eventName, message: $message);

                        return;
                    }
                } catch (Exception $e) {
                    // Fall through to session flash
                }
            }
        }

        // Fall back to session flash message
        session()->flash('email-change-notification', $message);
    }

    /**
     * Send cancellation notification to user.
     */
    protected function sendCancellationNotification(Model $user, $pendingChanges): void
    {
        // Get the first cancelled email for the notification
        $cancelledEmail = $pendingChanges->first()->new_email;
        $cancelledCount = $pendingChanges->count();

        $user->notify(new EmailChangeCancelled($cancelledEmail, $cancelledCount));
    }

    /**
     * Check if the email domain is allowed.
     */
    protected function isEmailDomainAllowed(string $email): bool
    {
        $blockedDomains = config('email-change-confirmation.blocked_domains', []);

        if (empty($blockedDomains)) {
            return true;
        }

        $domain = strtolower(substr(strrchr($email, '@'), 1));

        return ! in_array($domain, array_map('strtolower', $blockedDomains));
    }

    /**
     * Check if the user is within rate limits for email change requests.
     */
    protected function isWithinRateLimit(Model $user): bool
    {
        $maxRequests = config('email-change-confirmation.max_requests_per_hour', 5);

        if ($maxRequests <= 0) {
            return true; // No rate limiting
        }

        $emailChangeModel = config('email-change-confirmation.email_change_model');

        $recentRequests = $emailChangeModel::where('user_id', $user->getKey())
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return $recentRequests < $maxRequests;
    }
}
