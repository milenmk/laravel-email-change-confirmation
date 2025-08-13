<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use MilenMk\LaravelEmailChangeConfirmation\Facades\EmailChangeConfirmation;

/**
 * Example controller showing different ways to integrate
 * the email change confirmation package.
 */
class ProfileController extends Controller
{
    /**
     * Example 1: Automatic detection (recommended approach)
     * The package automatically detects email changes via model observers.
     */
    public function updateProfileAutomatic(Request $request): RedirectResponse
    {
        $user = auth()->user();
        
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        // Update user attributes
        $user->name = $request->input('name');
        $user->email = $request->input('email'); // Email change confirmation triggered automatically
        
        // Save the user
        $user->save();

        // Check if email was actually changed (it won't be if confirmation is required)
        if ($user->wasChanged('email')) {
            return back()->with('success', 'Profile updated successfully!');
        } else {
            // Email change confirmation was triggered
            return back()->with('info', 'Profile updated. Email change confirmation sent to your current email address.');
        }
    }

    /**
     * Example 2: Manual control using the service
     * Disable auto-detection in config and use this approach for full control.
     */
    public function updateProfileManual(Request $request): RedirectResponse
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        // Update name directly
        $user->name = $validated['name'];
        $user->save();

        // Handle email change manually
        $newEmail = $validated['email'];
        if ($user->email !== $newEmail) {
            if (EmailChangeConfirmation::validateEmailChange($user, $newEmail)) {
                EmailChangeConfirmation::requestEmailChange($user, $newEmail);
                return back()->with('success', 'Profile updated. Email change confirmation sent!');
            } else {
                return back()->withErrors(['email' => 'Invalid email change request.']);
            }
        }

        return back()->with('success', 'Profile updated successfully!');
    }

    /**
     * Example 3: Using the service class directly
     */
    public function updateProfileWithService(Request $request): RedirectResponse
    {
        $user = auth()->user();
        
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        // Update name
        $user->update(['name' => $request->input('name')]);

        // Handle email change
        $newEmail = $request->input('email');
        if ($user->email !== $newEmail) {
            try {
                app(\MilenMk\LaravelEmailChangeConfirmation\Services\EmailChangeService::class)
                    ->requestEmailChange($user, $newEmail);
                
                return back()->with('success', 'Profile updated. Please check your email to confirm the email change.');
            } catch (\Exception $e) {
                return back()->withErrors(['email' => $e->getMessage()]);
            }
        }

        return back()->with('success', 'Profile updated successfully!');
    }

    /**
     * Show pending email changes for the user
     */
    public function showPendingEmailChanges(Request $request)
    {
        $user = auth()->user();
        $pendingChanges = EmailChangeConfirmation::getPendingEmailChanges($user);
        
        return view('profile.pending-email-changes', compact('pendingChanges'));
    }

    /**
     * Cancel all pending email changes
     */
    public function cancelPendingEmailChanges(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $cancelled = EmailChangeConfirmation::cancelPendingEmailChanges($user);
        
        if ($cancelled > 0) {
            return back()->with('success', "Cancelled {$cancelled} pending email change(s).");
        }
        
        return back()->with('info', 'No pending email changes to cancel.');
    }

    /**
     * Example of checking if user can request email change
     */
    public function checkEmailChangeEligibility(Request $request)
    {
        $user = auth()->user();
        
        return response()->json([
            'can_request_change' => $user->canRequestEmailChange(),
            'has_pending_change' => $user->hasPendingEmailChange(),
            'pending_changes_count' => $user->pendingEmailChanges()->count(),
        ]);
    }
}