<?php

namespace App\Http\Controllers;

use MilenMk\LaravelEmailChangeConfirmation\Controllers\EmailChangeController as BaseController;
use MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use App\Notifications\EmailChangedSuccessfully;

/**
 * Example of extending the package controller to customize behavior.
 * Update your config to use this controller:
 * 
 * 'email_change_controller' => App\Http\Controllers\CustomEmailChangeController::class,
 */
class CustomEmailChangeController extends BaseController
{
    /**
     * Customize the successful confirmation handling
     */
    protected function handleSuccessfulConfirmation(EmailChange $emailChange): RedirectResponse
    {
        $user = $emailChange->user;
        
        // Log the email change for audit purposes
        Log::info('User email changed successfully', [
            'user_id' => $user->id,
            'old_email' => $emailChange->current_email,
            'new_email' => $emailChange->new_email,
            'confirmed_at' => $emailChange->change_confirmed_at,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Send a custom notification to the user
        $user->notify(new EmailChangedSuccessfully($emailChange));

        // Update user's last activity or any other custom logic
        $user->update(['last_email_change_at' => now()]);

        // Call parent method to handle the standard flow
        return parent::handleSuccessfulConfirmation($emailChange);
    }

    /**
     * Customize the successful denial handling
     */
    protected function handleSuccessfulDenial(EmailChange $emailChange): RedirectResponse
    {
        $user = $emailChange->user;
        
        // Log the denial for security monitoring
        Log::warning('Email change request denied', [
            'user_id' => $user->id,
            'attempted_email' => $emailChange->new_email,
            'current_email' => $emailChange->current_email,
            'denied_at' => $emailChange->change_denied_at,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // You might want to notify security team or take additional actions
        // if there are multiple denials from the same user
        $recentDenials = $user->emailChanges()
            ->denied()
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        if ($recentDenials >= 3) {
            Log::alert('Multiple email change denials detected', [
                'user_id' => $user->id,
                'denials_count' => $recentDenials,
            ]);
            
            // You could send an alert to security team here
            // or temporarily lock the account
        }

        return parent::handleSuccessfulDenial($emailChange);
    }

    /**
     * Customize the redirect destination
     */
    protected function getSuccessRedirect(): RedirectResponse
    {
        // Redirect to a custom route
        if (\Illuminate\Support\Facades\Route::has('profile.security')) {
            return redirect()->route('profile.security');
        }

        // Or redirect based on user role
        $user = auth()->user();
        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        // Fallback to parent implementation
        return parent::getSuccessRedirect();
    }

    /**
     * Add custom error handling
     */
    protected function redirectWithError(string $message): RedirectResponse
    {
        // Log security-related errors
        Log::warning('Email change confirmation error', [
            'message' => $message,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'url' => request()->fullUrl(),
        ]);

        return parent::redirectWithError($message);
    }

    /**
     * Override to add rate limiting or additional security checks
     */
    public function confirm(\MilenMk\LaravelEmailChangeConfirmation\Requests\EmailChangeRequest $request): RedirectResponse
    {
        // Add custom rate limiting
        $key = 'email_change_confirm:' . auth()->id();
        $maxAttempts = 5;
        $decayMinutes = 60;

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
            
            return $this->redirectWithError(
                "Too many confirmation attempts. Please try again in {$seconds} seconds."
            );
        }

        \Illuminate\Support\Facades\RateLimiter::hit($key, $decayMinutes * 60);

        // Call parent method
        $response = parent::confirm($request);

        // Clear rate limiting on successful confirmation
        if ($response->getSession()->has('success')) {
            \Illuminate\Support\Facades\RateLimiter::clear($key);
        }

        return $response;
    }
}