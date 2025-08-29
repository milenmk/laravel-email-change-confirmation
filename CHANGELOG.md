# Changelog

All notable changes to this project will be documented in this file.

## v1.2.1

#### Published: 2025-08-29

- Enhance README with responsive badge links
- Adds a list of other Laravel packages to the README
- Improved DISCLAIMER in README

## V1.2.0

#### Published at: 2025-08-14

- **Email Verification Reset**: Fixed issue where `email_verified_at` wasn't being properly reset to null during email
  change confirmation
- **Redirect Configuration**: Fixed email change confirmation redirects to properly respect configured routes for users
  without `MustVerifyEmail`

## V1.1.0-alpha

#### Published at: 2025-08-14

### Added

- **Configurable Redirect Routes**: Added configuration options for custom redirect routes after email change actions
    - `redirect_after_confirm` - Where to redirect after confirming email change
    - `redirect_after_deny` - Where to redirect after denying email change
    - `redirect_after_cancel` - Where to redirect after canceling pending change
- **Automatic Cleanup System**: Added automatic cleanup of expired email change requests
    - New `CleanupExpiredEmailChanges` job to mark expired pending requests as denied
    - New `email-change:cleanup-expired` Artisan command with `--queue` option
    - Configuration options: `auto_cleanup_expired` and `cleanup_schedule`
- **Enhanced Controller Methods**: Added separate redirect methods for different actions
    - `getConfiguredRedirect()` - Uses configured redirect routes
    - `getCancelRedirect()` - Specific redirect logic for cancel operations
    - `getSuccessRedirect()` - Enhanced with configurable route support

### Fixed

- **Mass Assignment Errors**: Fixed `email_verified_at` mass assignment errors that occurred when this field wasn't
  included in User model's `$fillable` array by using direct attribute assignment with `saveQuietly()`- **Authentication
  Issues**: Added proper `web` middleware to cancel-pending routes to fix 403 errors
- **Double Update Bug**: Removed redundant user update calls in email confirmation process

### Changed

- **Expired Request Handling**: Expired requests are now marked as `denied` instead of `expired` status
- **Route Middleware**: Enhanced route middleware configuration for better security
- **Documentation**: Updated README with comprehensive configuration examples and usage instructions

### Security

- **Fixed Mass Assignment Issues**: Resolved mass assignment errors when updating system-managed fields by using
  `updateQuietly()` for confirmed email changes and `email_verified_at` resets (prevents issues when these fields are
  not in User model's `$fillable` array)
- **Enhanced Route Security**: Added proper middleware stacking for all routes

## V1.0.1

#### Published at: 2025-08-14

- [NEW] Notification on cancel request
- Improved logging
- various bug fixes

## [1.0.0] - Initial Release

### Added

- Email change confirmation system with double opt-in security
- Automatic email change detection via model observers
- Livewire integration support
- Configurable security settings and rate limiting
- Email verification integration for Laravel's MustVerifyEmail
- Comprehensive test suite
- Detailed documentation and examples
