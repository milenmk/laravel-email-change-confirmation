# Laravel Email Change Confirmation

<div align="center">

<a href="https://packagist.org/packages/milenmk/laravel-email-change-confirmation">![Latest Version on Packagist](https://img.shields.io/packagist/v/milenmk/laravel-email-change-confirmation.svg?style=flat)</a>
<a href="https://packagist.org/packages/milenmk/laravel-email-change-confirmation">![Total Downloads](https://img.shields.io/packagist/dt/milenmk/laravel-email-change-confirmation.svg?style=flat)</a>
<a href="https://github.com/milenmk/laravel-email-change-confirmation">![GitHub User's stars](https://img.shields.io/github/stars/milenmk/laravel-email-change-confirmation)</a>
<a href="https://laravel.com/docs">![Laravel 10 Support](https://img.shields.io/badge/Laravel-10.x|11.x|12.x-orange?style=flat&logo=laravel)</a>
<a href="https://www.php.net">![PHP Version Support](https://img.shields.io/packagist/php-v/milenmk/laravel-email-change-confirmation?style=flat)</a>
<a href="https://github.com/milenmk/laravel-email-change-confirmation/blob/develop/LICENSE.md">![License](https://img.shields.io/packagist/l/milenmk/laravel-email-change-confirmation.svg?style=flat)</a>
<a href="https://github.com/milenmk/laravel-email-change-confirmation/issues">![Contributions Welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat)</a>
<a href="https://www.patreon.com/c/LaravelAddonsbyMilen">![Sponsor me](https://img.shields.io/badge/Sponsor-%E2%9D%A4-ff69b4?style=flat)</a>

</div>

A Laravel package that provides secure email change confirmation functionality. When users attempt to change their email
address, they must confirm the change via their current email address before the change takes effect.

## Features

- 🔒 **Secure email changes** - Users must confirm via their current email
- 📧 **Automatic email verification** - Integrates with Laravel's email verification
- 🎯 **Framework agnostic** - Works with any Laravel starter kit or custom application
- ⚡ **Livewire support** - Built-in support for Livewire applications
- 🔧 **Highly configurable** - Customize every aspect of the package
- 🎨 **Extensible** - Override controllers, notifications, and services
- 🚀 **Auto-detection** - Automatically detects email changes using model observers
- 📱 **Responsive emails** - Beautiful, mobile-friendly confirmation emails

## Installation

### Requirements

- PHP 8.3 or higher
- Laravel 10.0, 11.0, or 12.0
- User model must use the `Notifiable` trait

Install the package via Composer:

```bash
composer require milenmk/laravel-email-change-confirmation
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="email-change-confirmation-migrations"
php artisan migrate
```

Optionally, publish the configuration file:

```bash
php artisan vendor:publish --tag="email-change-confirmation-config"
```

Add the `HasEmailChangeConfirmation` trait to your user model

### Security Configuration (Recommended)

For enhanced security, add a hash secret to your `.env` file:

```bash
# Generate a secure 32-byte base64-encoded secret
php -r "echo 'EMAIL_CHANGE_HASH_SECRET=' . base64_encode(random_bytes(32)) . PHP_EOL;"
```

Add the generated line to your `.env` file. This enables HMAC-based hashing instead of plain SHA-256 for better
security.

## Quick Start

### 1. Add the Trait to Your User Model

Add the `HasEmailChangeConfirmation` trait to your User model:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use MilenMk\LaravelEmailChangeConfirmation\Traits\HasEmailChangeConfirmation;

class User extends Authenticatable
{
    use HasEmailChangeConfirmation;

    // ... rest of your model
}
```

### 2. Ensure Your User Model Uses the Notifiable Trait

The package requires the `Notifiable` trait to send emails:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use MilenMk\LaravelEmailChangeConfirmation\Traits\HasEmailChangeConfirmation;

class User extends Authenticatable
{
    use Notifiable, HasEmailChangeConfirmation;

    // ... rest of your model
}
```

### 3. That's It!

The package will automatically detect email changes and handle the confirmation process. When a user tries to change
their email:

1. The original email remains unchanged
2. A confirmation email is sent to the current email address
3. The user must click "Confirm" to complete the change
4. If the user implements `MustVerifyEmail`, a verification email is sent to the new address

## Usage

### Automatic Detection (Recommended)

By default, the package automatically detects email changes using model observers. Simply update the user's email as you
normally would:

```php
// In a controller
$user = auth()->user();
$user->email = 'new@example.com';
$user->save(); // Email change confirmation is automatically triggered
```

```php
// In a Livewire component
public function updateEmail()
{
    $this->user->email = $this->newEmail;
    $this->user->save(); // Automatically handled
}
```

### Manual Integration

If you prefer manual control, disable auto-detection in the config and use the service directly:

```php
use MilenMk\LaravelEmailChangeConfirmation\Facades\EmailChangeConfirmation;

// Request an email change
$emailChange = EmailChangeConfirmation::requestEmailChange($user, 'new@example.com');

// Check pending changes
$pendingChanges = EmailChangeConfirmation::getPendingEmailChanges($user);

// Cancel pending changes
$cancelled = EmailChangeConfirmation::cancelPendingEmailChanges($user);
```

### Displaying Pending Email Changes

When a user has a pending email change, you can display a notification with a cancel button:

```blade
{{-- In your Blade template --}}
@if (auth()->user()->hasPendingEmailChange())
    @php
        $pendingChange = auth()
            ->user()
            ->getLatestPendingEmailChange();
    @endphp

    <div class="alert alert-warning">
        <strong>Pending Email Change</strong>
        <br />
        You have requested to change your email to
        <strong>{{ $pendingChange->new_email }}</strong>
        .
        <br />
        Please check your current email address ({{ $pendingChange->current_email }}) for confirmation instructions.
        <hr />
        <form method="POST" action="{{ route('email-change-confirmation.cancel-pending') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-danger">Cancel Request</button>
        </form>
    </div>
@endif
```

### Using the Service Class

```php
use MilenMk\LaravelEmailChangeConfirmation\Services\EmailChangeService;

class ProfileController extends Controller
{
    public function updateEmail(Request $request, EmailChangeService $emailChangeService)
    {
        $request->validate(['email' => 'required|email']);

        $user = auth()->user();
        $newEmail = $request->input('email');

        if ($emailChangeService->validateEmailChange($user, $newEmail)) {
            $emailChangeService->requestEmailChange($user, $newEmail);
            return back()->with('success', 'Email change confirmation sent!');
        }

        return back()->withErrors(['email' => 'Invalid email change request.']);
    }
}
```

## Livewire Integration

The package provides seamless Livewire integration:

```php
<?php

namespace App\Livewire;

use Livewire\Component;

class UpdateProfile extends Component
{
    public $email;

    public function updateEmail()
    {
        $this->validate(['email' => 'required|email']);

        auth()
            ->user()
            ->update(['email' => $this->email]);

        // The package automatically handles the rest and dispatches
        // a browser event for UI feedback
    }

    public function render()
    {
        return view('livewire.update-profile');
    }
}
```

In your Blade template:

```blade
<div
    x-data="{ showNotification: false }"
    @email-change-notification.window="showNotification = true; setTimeout(() => showNotification = false, 5000)"
>
    <div x-show="showNotification" class="alert alert-info">
        Email change confirmation sent! Check your current email address.
    </div>

    <!-- Your form here -->
</div>
```

## Configuration

The package is highly configurable. Here are the key configuration options:

```php
// config/email-change-confirmation.php

return [
    // User model to use
    'user_model' => App\Models\User::class,

    // Auto-detect email changes (recommended)
    'auto_detect_email_changes' => true,

    // Route configuration
    'route_prefix' => 'email-change',
    'middleware' => ['web', 'auth', 'signed'],

    // Redirect routes after actions
    'redirect_after_confirm' => null, // e.g., 'dashboard' or 'profile.edit'
    'redirect_after_deny' => null, // e.g., 'dashboard' or 'profile.edit'
    'redirect_after_cancel' => null, // e.g., 'dashboard' or 'profile.edit'

    // Email settings
    'confirmation_email_expire_minutes' => 60,
    'from_email' => null,
    'from_name' => null,

    // Cleanup configuration
    'auto_cleanup_expired' => true,
    'cleanup_schedule' => 'hourly', // hourly, daily, weekly

    // Notification settings
    'send_notification_to_user' => true,
    'notification_message' => 'Email change confirmation sent...',

    // Email verification integration
    'auto_send_email_verification' => true,

    // Security settings
    'hash_algorithm' => 'sha256',
    'hash_secret' => env('EMAIL_CHANGE_HASH_SECRET'),
    'max_pending_changes_per_user' => 1,
    'max_requests_per_hour' => 5,
    'blocked_domains' => [],

    // Customization - override these classes
    'email_change_model' => MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange::class,
    'email_change_controller' => MilenMk\LaravelEmailChangeConfirmation\Controllers\EmailChangeController::class,
    'email_change_notification' => MilenMk\LaravelEmailChangeConfirmation\Notifications\EmailChangeConfirmation::class,
    'email_change_service' => MilenMk\LaravelEmailChangeConfirmation\Services\EmailChangeService::class,

    // Livewire integration
    'livewire_enabled' => true,
    'livewire_notification_event' => 'email-change-notification',
];
```

## Automatic Cleanup of Expired Requests

The package provides automatic cleanup of expired email change requests to prevent database bloat and security issues.

### Manual Cleanup

You can manually clean up expired requests using the provided Artisan command:

```bash
# Run cleanup synchronously
php artisan email-change:cleanup-expired

# Dispatch cleanup job to queue
php artisan email-change:cleanup-expired --queue
```

### Automatic Cleanup with Task Scheduling

To automatically clean up expired requests, add the command to your `app/Console/Kernel.php`:

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Clean up expired email changes every hour
    $schedule->command('email-change:cleanup-expired')
        ->hourly()
        ->withoutOverlapping();

    // Or run daily at 2 AM
    $schedule->command('email-change:cleanup-expired')
        ->dailyAt('02:00')
        ->withoutOverlapping();
}
```

### Configuration

Configure cleanup behavior in your config file:

```php
// config/email-change-confirmation.php

'auto_cleanup_expired' => true,        // Enable automatic cleanup
'cleanup_schedule' => 'hourly',        // How often to run (hourly, daily, weekly)
'confirmation_email_expire_minutes' => 60, // When requests expire
```

When expired requests are cleaned up, they are marked as `denied` with a `denied_at` timestamp, preserving the audit
trail while preventing them from being used.

## Customization

### Extending the Controller

Create your own controller that extends the package controller:

```php
<?php

namespace App\Http\Controllers;

use MilenMk\LaravelEmailChangeConfirmation\Controllers\EmailChangeController as BaseController;
use MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange;
use Illuminate\Http\RedirectResponse;

class CustomEmailChangeController extends BaseController
{
    protected function handleSuccessfulConfirmation(EmailChange $emailChange): RedirectResponse
    {
        // Custom logic after successful confirmation

        // Log the email change
        \Log::info('Email changed', [
            'user_id' => $emailChange->user_id,
            'old_email' => $emailChange->current_email,
            'new_email' => $emailChange->new_email,
        ]);

        // Send custom notification
        $emailChange->user->notify(new \App\Notifications\EmailChangedNotification());

        return parent::handleSuccessfulConfirmation($emailChange);
    }

    protected function getSuccessRedirect(): RedirectResponse
    {
        // Custom redirect logic
        return redirect()->route('profile.settings');
    }
}
```

Update your configuration:

```php
// config/email-change-confirmation.php
'email_change_controller' => App\Http\Controllers\CustomEmailChangeController::class,
```

### Configuring Redirects

You can configure where users are redirected after email change actions:

```php
// config/email-change-confirmation.php

'redirect_after_confirm' => 'dashboard',     // After confirming email change
'redirect_after_deny' => 'profile.edit',    // After denying email change
'redirect_after_cancel' => 'profile.edit',  // After canceling pending change
```

If no redirect route is configured, the package will try common routes like `dashboard`, `home`, `profile.show`, or
`profile`, and fall back to the root URL (`/`).

For the cancel action specifically, if no redirect is configured, it will use `back()` to return to the previous page.

### Custom Notification

Create your own notification class:

```php
<?php

namespace App\Notifications;

use MilenMk\LaravelEmailChangeConfirmation\Notifications\EmailChangeConfirmation as BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

class CustomEmailChangeNotification extends BaseNotification
{
    protected function buildMailMessage(string $confirmUrl, string $denyUrl): MailMessage
    {
        return (new MailMessage())
            ->subject('Confirm Your Email Change - ' . config('app.name'))
            ->greeting('Hello ' . $this->username . '!')
            ->line('We received a request to change your email address.')
            ->line('New email address: **' . $this->newEmail . '**')
            ->action('Confirm Email Change', $confirmUrl)
            ->line('If you did not request this change, please click the deny button below.')
            ->action('Deny Request', $denyUrl)
            ->line(
                'This link will expire in ' .
                    config('email-change-confirmation.confirmation_email_expire_minutes') .
                    ' minutes.',
            );
    }
}
```

### Custom Service

Extend the service for custom business logic:

```php
<?php

namespace App\Services;

use MilenMk\LaravelEmailChangeConfirmation\Services\EmailChangeService as BaseService;
use Illuminate\Database\Eloquent\Model;

class CustomEmailChangeService extends BaseService
{
    public function requestEmailChange(Model $user, string $newEmail): EmailChange
    {
        // Custom validation
        if ($this->isEmailBlacklisted($newEmail)) {
            throw new \Exception('This email domain is not allowed.');
        }

        // Custom rate limiting
        if ($this->hasRecentEmailChangeAttempt($user)) {
            throw new \Exception('Please wait before requesting another email change.');
        }

        return parent::requestEmailChange($user, $newEmail);
    }

    private function isEmailBlacklisted(string $email): bool
    {
        // Your custom logic
        return false;
    }

    private function hasRecentEmailChangeAttempt(Model $user): bool
    {
        // Your custom logic
        return false;
    }
}
```

## Working with Different Laravel Setups

### Laravel Breeze

Works out of the box. Just add the trait to your User model.

### Laravel Jetstream

Works with both Livewire and Inertia stacks. For Inertia, you'll need to handle the frontend notifications manually.

### Laravel Fortify

The package integrates seamlessly with Fortify's profile update actions.

### Custom Applications

The package is designed to work with any Laravel application structure. Use manual integration if auto-detection doesn't
work for your setup.

### Trait Methods

```php
// Check if user has pending email changes
$user->hasPendingEmailChange(): bool

// Get pending email changes
$user->pendingEmailChanges(): HasMany

// Get latest pending email change
$user->getLatestPendingEmailChange(): ?EmailChange

// Check if user can request email change
$user->canRequestEmailChange(): bool
```

### Service Methods

```php
// Request email change
EmailChangeConfirmation::requestEmailChange($user, $newEmail): EmailChange

// Confirm email change
EmailChangeConfirmation::confirmEmailChange($emailChange): bool

// Deny email change
EmailChangeConfirmation::denyEmailChange($emailChange): bool

// Get pending changes
EmailChangeConfirmation::getPendingEmailChanges($user): Collection

// Cancel pending changes
EmailChangeConfirmation::cancelPendingEmailChanges($user): int

// Validate email change
EmailChangeConfirmation::validateEmailChange($user, $newEmail): bool
```

### Model Methods

```php
// Check status
$emailChange->isConfirmed(): bool
$emailChange->isDenied(): bool
$emailChange->isPending(): bool

// Update status
$emailChange->confirm(): bool
$emailChange->deny(): bool
```

## Security Features

- **Signed URLs**: All confirmation links use Laravel's signed URL feature
- **HMAC Hash verification**: Email addresses are hashed using HMAC-SHA256 for additional security
- **Time-based expiration**: Confirmation links expire after a configurable time
- **User verification**: Multiple layers of user identity verification
- **Rate limiting**: Configurable limits on pending email changes and requests per hour
- **Domain blocking**: Block disposable/temporary email domains
- **Security logging**: Comprehensive logging of security events for monitoring

### Security Configuration

The package includes several security features that can be configured:

#### Hash Secret (Recommended)

Set `EMAIL_CHANGE_HASH_SECRET` in your `.env` file for enhanced security:

- **Minimum length**: 16 characters (32+ recommended)
- **Maximum length**: No limit (but 64 characters is sufficient)
- **Allowed characters**: Any printable ASCII characters, base64-encoded strings recommended
- **Generation**: Use `php -r "echo base64_encode(random_bytes(32));"` for a secure 44-character base64 string

#### Rate Limiting

- `max_requests_per_hour`: Limit email change requests per user (default: 5)
- Route-level throttling: Additional protection at the HTTP level

#### Domain Blocking

- `blocked_domains`: Array of domains to block (e.g., temporary email services)
- Case-insensitive matching

#### Expiration Settings

- `confirmation_email_expire_minutes`: How long confirmation links remain valid (default: 60, recommended: 30 or less)

## Verification Steps

After installation, verify everything is working:

### 1. Check Database Tables

Ensure the `email_changes` table was created:

```sql
DESCRIBE email_changes;
```

### 2. Test Email Change

1. Log into your application
2. Try to change your email address
3. Check that:
    - The email in the database doesn't change immediately
    - You receive a confirmation email at your current address
    - The email contains confirm and deny buttons

### 3. Test Confirmation Flow

1. Click the "Confirm" button in the email
2. Verify that:
    - Your email address is updated in the database
    - If you implement `MustVerifyEmail`, you receive a verification email at the new address
    - You're redirected to the appropriate page

### 4. Test Denial Flow

1. Request another email change
2. Click the "Deny" button in the email
3. Verify that:
    - The email change is marked as denied
    - Your original email address remains unchanged
    - You're redirected to the appropriate page

## Troubleshooting

### Issue: Emails Not Sending

**Solution:**

1. Check your mail configuration in `.env`
2. Test mail sending with `php artisan tinker`:
    ```php
    Mail::raw('Test email', function ($message) {
        $message->to('test@example.com')->subject('Test');
    });
    ```
3. Check your application logs for mail errors

### Issue: User Model Doesn't Have Notifiable Trait

**Error:** `User model must use the Notifiable trait`

**Solution:**
Add the `Notifiable` trait to your User model:

```php
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
    // ...
}
```

### Issue: Auto-Detection Not Working

**Solution:**

1. Ensure the `HasEmailChangeConfirmation` trait is added to your User model
2. Check that `auto_detect_email_changes` is `true` in config
3. If still not working, try manual integration

### Issue: Routes Not Working

**Solution:**

1. Clear route cache: `php artisan route:clear`
2. Check that routes are registered: `php artisan route:list | grep email-change`
3. Ensure middleware configuration is correct

### Issue: Migration Fails

**Solution:**

1. Check if you have existing `email_changes` table
2. If using UUIDs for users, ensure the migration handles this correctly
3. Check database connection and permissions

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

See [SECURITY.md](SECURITY.md) for more information on how to report security vulnerabilities.

## Credits

- [Milen MK](https://github.com/milenmk)
- [All Contributors](CONTRIBUTORS.md)

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Support My Work

If this package saves you time, you can support ongoing development:  
👉 [Become a Patron](https://www.patreon.com/c/LaravelAddonsbyMilen)

## Other Packages

Check out my other Laravel packages:

- **[Laravel GDPR Cookie Manager](https://packagist.org/packages/milenmk/laravel-gdpr-cookie-manager)** - GDPR-compliant
  cookie consent management with user preference tracking
- **[Laravel Blacklist](https://packagist.org/packages/milenmk/laravel-blacklist)** - A Laravel package for blacklist
  validation of user input
- **[Laravel GDPR Exporter](https://packagist.org/packages/milenmk/laravel-gdpr-exporter)** - GDPR-compliant data export
  functionality
- **[Laravel Locations](https://packagist.org/packages/milenmk/laravel-locations)** - Add Countries, Cities, Areas,
  Languages and Currencies models to your Laravel application
- **[Laravel Rate Limiting](https://packagist.org/packages/milenmk/laravel-rate-limiting)** - Advanced rate limiting
  capabilities with exponential backoff
- **[Laravel Datatables and Forms](https://packagist.org/packages/milenmk/laravel-simple-datatables-and-forms)** - Easy
  to use package to create datatables and forms for Livewire components

## License

This package is licensed under the MIT License. See the [LICENSE](LICENSE.md) file for more details.

## Disclaimer

This package is provided "as is", without warranty of any kind, express or implied, including but not limited to
warranties of merchantability, fitness for a particular purpose, or noninfringement.

The author(s) make no guarantees regarding the accuracy, reliability, or completeness of the code, and shall not be held
liable for any damages or losses arising from its use.

Please ensure you thoroughly test this package in your environment before deploying it to production.
