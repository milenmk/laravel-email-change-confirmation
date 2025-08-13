<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Validation\Rule;

/**
 * Example Livewire component showing how to handle email changes
 * with the email change confirmation package.
 */
class UpdateProfileForm extends Component
{
    public $name = '';
    public $email = '';
    
    public function mount()
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateProfile()
    {
        $user = auth()->user();
        
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        // Update name directly
        $user->name = $this->name;
        
        // Update email - the package will automatically handle email change confirmation
        $user->email = $this->email;
        
        // Save the user - email change confirmation is triggered automatically
        $user->save();

        // The package will dispatch an 'email-change-notification' event if email was changed
        // and send a session flash message as fallback
        
        if (!$user->wasChanged('email')) {
            // Only show success if email wasn't changed (no confirmation needed)
            session()->flash('success', 'Profile updated successfully!');
        }
        
        // If email was changed, the package handles the notification
    }

    public function render()
    {
        return view('livewire.update-profile-form');
    }
}

// Corresponding Blade template (resources/views/livewire/update-profile-form.blade.php):
/*
<div x-data="{ showNotification: false, notificationMessage: '' }" 
     @email-change-notification.window="
        showNotification = true; 
        notificationMessage = $event.detail.message;
        setTimeout(() => showNotification = false, 5000)
     ">
    
    <!-- Notification for email change -->
    <div x-show="showNotification" 
         x-transition
         class="mb-4 p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded">
        <span x-text="notificationMessage"></span>
    </div>
    
    <!-- Success message for other updates -->
    @if (session()->has('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif
    
    <!-- Session-based email change notification (fallback) -->
    @if (session()->has('email-change-notification'))
        <div class="mb-4 p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded">
            {{ session('email-change-notification') }}
        </div>
    @endif

    <form wire:submit="updateProfile">
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" 
                   id="name" 
                   wire:model="name" 
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" 
                   id="email" 
                   wire:model="email" 
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            
            @if(auth()->user()->hasPendingEmailChange())
                <p class="mt-1 text-sm text-yellow-600">
                    You have a pending email change. Check your current email for confirmation instructions.
                </p>
            @endif
        </div>

        <button type="submit" 
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Update Profile
        </button>
    </form>
</div>
*/