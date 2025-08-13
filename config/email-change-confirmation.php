<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model that will be used for email change confirmation.
    | By default, it will use the model defined in auth.providers.users.model
    |
    */
    'user_model' => config('auth.providers.users.model', App\Models\User::class),

    /*
    |--------------------------------------------------------------------------
    | Auto Detect Email Changes
    |--------------------------------------------------------------------------
    |
    | When enabled, the package will automatically detect email changes
    | using model observers. If disabled, you'll need to manually trigger
    | email change confirmations using the EmailChangeService.
    |
    */
    'auto_detect_email_changes' => true,

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the routes used by the email change confirmation system.
    |
    */
    'route_prefix' => 'email-change',
    'middleware' => ['web', 'auth', 'signed'],

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the email settings for the confirmation emails.
    |
    */
    'confirmation_email_expire_minutes' => 60,
    'from_email' => null, // Uses default mail from address if null
    'from_name' => null, // Uses default mail from name if null

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how users are notified about email change requests.
    |
    */
    'send_notification_to_user' => true,
    'notification_message' => 'An email change request has been submitted. Please check your current email address for confirmation instructions.',

    /*
    |--------------------------------------------------------------------------
    | Email Verification Integration
    |--------------------------------------------------------------------------
    |
    | When enabled, the package will automatically send email verification
    | notifications after a successful email change, but only if the user
    | model implements MustVerifyEmail.
    |
    */
    'auto_send_email_verification' => true,

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the database table and connection used for email changes.
    |
    */
    'table_name' => 'email_changes',
    'connection' => null, // Uses default database connection if null

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security settings for email change confirmations.
    |
    */
    'hash_algorithm' => 'sha256',
    'hash_secret' => env('EMAIL_CHANGE_HASH_SECRET'),
    'require_current_password' => false, // Future feature
    'max_pending_changes_per_user' => 1,
    'max_requests_per_hour' => 5,
    'blocked_domains' => [],

    /*
    |--------------------------------------------------------------------------
    | Customization
    |--------------------------------------------------------------------------
    |
    | Override default classes to customize the package behavior.
    |
    */
    'email_change_model' => MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange::class,
    'email_change_controller' => MilenMk\LaravelEmailChangeConfirmation\Controllers\EmailChangeController::class,
    'email_change_notification' => MilenMk\LaravelEmailChangeConfirmation\Notifications\EmailChangeConfirmation::class,
    'email_change_service' => MilenMk\LaravelEmailChangeConfirmation\Services\EmailChangeService::class,

    /*
    |--------------------------------------------------------------------------
    | Livewire Integration
    |--------------------------------------------------------------------------
    |
    | Configure Livewire integration settings.
    |
    */
    'livewire_enabled' => class_exists(\Livewire\Component::class),
    'livewire_notification_event' => 'email-change-notification',
];
