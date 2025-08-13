<?php

declare(strict_types=1);

namespace MilenMk\LaravelEmailChangeConfirmation\Controllers;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange;
use MilenMk\LaravelEmailChangeConfirmation\Requests\EmailChangeRequest;
use MilenMk\LaravelEmailChangeConfirmation\Services\EmailChangeService;

class EmailChangeController extends Controller
{
    protected EmailChangeService $emailChangeService;

    public function __construct(EmailChangeService $emailChangeService)
    {
        $this->emailChangeService = $emailChangeService;
    }

    /**
     * Confirm an email change request.
     */
    public function confirm(EmailChangeRequest $request): RedirectResponse
    {
        $emailChange = $this->findEmailChange($request);

        if (! $emailChange || ! $emailChange->isPending()) {
            return $this->redirectWithError('Invalid or expired email change request.');
        }

        try {
            $success = $this->emailChangeService->confirmEmailChange($emailChange);

            if ($success) {
                return $this->handleSuccessfulConfirmation($emailChange);
            } else {
                return $this->redirectWithError('Failed to confirm email change.');
            }
        } catch (Exception $e) {
            // Log the full error for debugging
            Log::error('Email change confirmation failed', [
                'user_id' => $request->user()->id,
                'email_change_id' => $request->route('email_change'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return generic error to user to prevent information disclosure
            return $this->redirectWithError(
                'An error occurred while confirming the email change. Please try again or contact support.',
            );
        }
    }

    /**
     * Deny an email change request.
     */
    public function deny(EmailChangeRequest $request): RedirectResponse
    {
        $emailChange = $this->findEmailChange($request);

        if (! $emailChange || ! $emailChange->isPending()) {
            return $this->redirectWithError('Invalid or expired email change request.');
        }

        try {
            $success = $this->emailChangeService->denyEmailChange($emailChange);

            if ($success) {
                return $this->handleSuccessfulDenial($emailChange);
            } else {
                return $this->redirectWithError('Failed to deny email change.');
            }
        } catch (Exception $e) {
            // Log the full error for debugging
            Log::error('Email change denial failed', [
                'user_id' => $request->user()->id,
                'email_change_id' => $request->route('email_change'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return generic error to user to prevent information disclosure
            return $this->redirectWithError(
                'An error occurred while denying the email change. Please try again or contact support.',
            );
        }
    }

    /**
     * Request a new email change (for manual integration).
     * This method can be called from your application controllers.
     */
    public function requestChange(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $user = $request->user();
        $newEmail = $request->input('email');

        if (! $this->emailChangeService->validateEmailChange($user, $newEmail)) {
            return back()->withErrors(['email' => 'Invalid email change request.']);
        }

        try {
            $this->emailChangeService->requestEmailChange($user, $newEmail);

            return back()->with(
                'success',
                'Email change request submitted. Please check your current email for confirmation instructions.',
            );
        } catch (Exception $e) {
            // Log the full error for debugging
            Log::error('Email change request failed', [
                'user_id' => $user->id,
                'new_email' => $newEmail,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return generic error to user to prevent information disclosure
            return back()->withErrors([
                'email' => 'Unable to process email change request. Please try again or contact support.',
            ]);
        }
    }

    /**
     * Cancel pending email changes for the authenticated user.
     */
    public function cancelPending(Request $request): RedirectResponse
    {
        $user = $request->user();

        try {
            $cancelled = $this->emailChangeService->cancelPendingEmailChanges($user);

            if ($cancelled > 0) {
                return back()->with('success', "Cancelled {$cancelled} pending email change(s).");
            } else {
                return back()->with('info', 'No pending email changes to cancel.');
            }
        } catch (Exception $e) {
            // Log the full error for debugging
            Log::error('Cancel pending email changes failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return generic error to user to prevent information disclosure
            return back()->withErrors([
                'error' => 'Failed to cancel pending email changes. Please try again or contact support.',
            ]);
        }
    }

    /**
     * Get pending email changes for the authenticated user.
     */
    public function getPending(Request $request)
    {
        $user = $request->user();

        return $this->emailChangeService->getPendingEmailChanges($user);
    }

    /**
     * Find the email change record from the request.
     */
    protected function findEmailChange(EmailChangeRequest $request): ?EmailChange
    {
        $emailChangeModel = config('email-change-confirmation.email_change_model');

        return $emailChangeModel::find($request->route('email_change'));
    }

    /**
     * Handle successful email change confirmation.
     */
    protected function handleSuccessfulConfirmation(EmailChange $emailChange): RedirectResponse
    {
        $user = $emailChange->user;

        // Check if user implements MustVerifyEmail and redirect accordingly
        if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && is_null($user->email_verified_at)) {
            return redirect()
                ->route('verification.notice')
                ->with('success', 'Email change confirmed successfully. Please verify your new email address.');
        }

        return $this->getSuccessRedirect()->with('success', 'Email change confirmed successfully.');
    }

    /**
     * Handle successful email change denial.
     */
    protected function handleSuccessfulDenial(EmailChange $emailChange): RedirectResponse
    {
        return $this->getSuccessRedirect()->with('success', 'Email change request has been denied successfully.');
    }

    /**
     * Get the redirect response for successful operations.
     * Override this method to customize the redirect destination.
     */
    protected function getSuccessRedirect(): RedirectResponse
    {
        // Try common route names
        $routes = ['dashboard', 'home', 'profile.show', 'profile'];

        foreach ($routes as $route) {
            if (Route::has($route)) {
                return redirect()->route($route);
            }
        }

        // Fallback to root
        return redirect('/');
    }

    /**
     * Redirect with error message.
     */
    protected function redirectWithError(string $message): RedirectResponse
    {
        return $this->getSuccessRedirect()->with('error', $message);
    }
}
