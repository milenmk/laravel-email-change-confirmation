# Installation Guide

This guide will walk you through installing and configuring the Laravel Email Change Confirmation package in different Laravel setups.

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- A working mail configuration in your Laravel application
- User model with the `Notifiable` trait

## Step 1: Install the Package

```bash
composer require milenmk/laravel-email-change-confirmation
```

## Step 2: Publish and Run Migrations

```bash
# Publish the migration files
php artisan vendor:publish --tag="email-change-confirmation-migrations"

# Run the migrations
php artisan migrate
```

## Step 3: Configure Your User Model

Add the `HasEmailChangeConfirmation` trait to your User model:

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

## Step 4: Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag="email-change-confirmation-config"
```

This will create `config/email-change-confirmation.php` where you can customize the package behavior.

## Step 5: Configure Mail Settings

Ensure your application has proper mail configuration in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Installation for Different Laravel Setups

### Laravel Breeze

Laravel Breeze works out of the box with this package:

1. Follow the standard installation steps above
2. The package will automatically detect email changes in Breeze's profile update functionality
3. No additional configuration needed

### Laravel Jetstream (Livewire)

For Jetstream with Livewire:

1. Follow the standard installation steps
2. The package automatically integrates with Jetstream's profile management
3. Livewire notifications are automatically enabled

### Laravel Jetstream (Inertia)

For Jetstream with Inertia:

1. Follow the standard installation steps
2. You may need to handle frontend notifications manually since Inertia uses Vue/React
3. Consider disabling Livewire integration in config:

```php
// config/email-change-confirmation.php
'livewire_enabled' => false,
```

### Laravel Fortify

The package integrates seamlessly with Fortify:

1. Follow the standard installation steps
2. The package will automatically hook into Fortify's profile update actions
3. No additional configuration needed

### Custom Laravel Applications

For custom applications:

1. Follow the standard installation steps
2. If auto-detection doesn't work, you can disable it and use manual integration:

```php
// config/email-change-confirmation.php
'auto_detect_email_changes' => false,
```

3. Then use the service manually in your controllers:

```php
use MilenMk\LaravelEmailChangeConfirmation\Facades\EmailChangeConfirmation;

public function updateEmail(Request $request)
{
    $user = auth()->user();
    $newEmail = $request->input('email');
    
    if (EmailChangeConfirmation::validateEmailChange($user, $newEmail)) {
        EmailChangeConfirmation::requestEmailChange($user, $newEmail);
        return back()->with('success', 'Email change confirmation sent!');
    }
    
    return back()->withErrors(['email' => 'Invalid email change request.']);
}
```

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

## Advanced Configuration

### Custom User Model

If your user model is not in the default location:

```php
// config/email-change-confirmation.php
'user_model' => App\Models\CustomUser::class,
```

### Custom Table Name

To use a different table name:

```php
// config/email-change-confirmation.php
'table_name' => 'custom_email_changes',
```

### Custom Routes

To customize route configuration:

```php
// config/email-change-confirmation.php
'route_prefix' => 'custom-email-change',
'middleware' => ['web', 'auth', 'signed', 'throttle:6,1'],
```

### Email Customization

To customize email settings:

```php
// config/email-change-confirmation.php
'confirmation_email_expire_minutes' => 120, // 2 hours
'from_email' => 'security@yourapp.com',
'from_name' => 'Security Team',
```

## Next Steps

After successful installation:

1. Read the [README.md](README.md) for usage examples
2. Check the [examples](examples/) directory for integration patterns
3. Customize the package behavior using the configuration file
4. Consider extending the controllers or notifications for your specific needs

## Support

If you encounter issues during installation:

1. Check the troubleshooting section above
2. Review the package documentation
3. Check existing GitHub issues
4. Create a new issue with detailed information about your setup