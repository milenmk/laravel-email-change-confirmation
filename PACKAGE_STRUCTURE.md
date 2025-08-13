# Package Structure

This document outlines the complete structure of the Laravel Email Change Confirmation package.

## Directory Structure

```
laravel-email-change-confirmation/
├── src/
│   ├── Controllers/
│   │   └── EmailChangeController.php          # Main controller for handling confirmations
│   ├── Models/
│   │   └── EmailChange.php                    # Email change model
│   ├── Notifications/
│   │   └── EmailChangeConfirmation.php        # Email notification class
│   ├── Observers/
│   │   └── UserObserver.php                   # Auto-detects email changes
│   ├── Requests/
│   │   └── EmailChangeRequest.php             # Form request for validation
│   ├── Services/
│   │   └── EmailChangeService.php             # Core business logic
│   ├── Traits/
│   │   └── HasEmailChangeConfirmation.php     # User model trait
│   ├── Facades/
│   │   └── EmailChangeConfirmation.php        # Facade for easy access
│   └── EmailChangeConfirmationServiceProvider.php  # Service provider
├── database/
│   └── migrations/
│       └── 2024_01_01_000000_create_email_changes_table.php
├── config/
│   └── email-change-confirmation.php          # Configuration file
├── routes/
│   └── web.php                                # Package routes
├── examples/                                  # Usage examples
│   ├── UserModel.php                         # Example User model
│   ├── LivewireComponent.php                 # Livewire integration
│   ├── ControllerExample.php                 # Controller examples
│   ├── CustomController.php                  # Custom controller extension
│   ├── CustomNotification.php                # Custom notification
│   └── BladeTemplates.blade.php              # Blade template examples
├── tests/
│   └── EmailChangeServiceTest.php             # Test examples
├── composer.json                              # Package definition
├── README.md                                  # Main documentation
├── INSTALLATION.md                            # Installation guide
├── CONTRIBUTING.md                            # Contribution guidelines
├── CHANGELOG.md                               # Version history
├── LICENSE.md                                 # MIT license
└── PACKAGE_STRUCTURE.md                       # This file
```

## Core Components

### 1. EmailChangeService
**Location:** `src/Services/EmailChangeService.php`

The main service class that handles all email change operations:
- `requestEmailChange()` - Initiates email change process
- `confirmEmailChange()` - Confirms pending email change
- `denyEmailChange()` - Denies pending email change
- `validateEmailChange()` - Validates email change requests
- `getPendingEmailChanges()` - Gets pending changes for user
- `cancelPendingEmailChanges()` - Cancels pending changes

### 2. EmailChange Model
**Location:** `src/Models/EmailChange.php`

Eloquent model for email change records:
- Stores current and new email addresses
- Tracks confirmation/denial status
- Provides helper methods for status checking
- Includes scopes for filtering records

### 3. HasEmailChangeConfirmation Trait
**Location:** `src/Traits/HasEmailChangeConfirmation.php`

User model trait that provides:
- Relationship methods to email changes
- Helper methods for checking pending changes
- Integration with Laravel's email verification

### 4. UserObserver
**Location:** `src/Observers/UserObserver.php`

Model observer that automatically detects email changes:
- Monitors User model updates
- Intercepts email changes
- Triggers confirmation process automatically

### 5. EmailChangeController
**Location:** `src/Controllers/EmailChangeController.php`

Controller handling confirmation/denial requests:
- Processes signed URLs from emails
- Validates requests using EmailChangeRequest
- Handles success/error redirects
- Extensible for custom behavior

### 6. EmailChangeConfirmation Notification
**Location:** `src/Notifications/EmailChangeConfirmation.php`

Email notification sent to users:
- Beautiful, responsive email template
- Confirm and deny buttons
- Security warnings
- Customizable content and styling

### 7. Configuration
**Location:** `config/email-change-confirmation.php`

Comprehensive configuration options:
- User model configuration
- Auto-detection settings
- Route and middleware configuration
- Email settings
- Security options
- Customization options

## Key Features

### 🔒 Security Features
- Signed URLs with expiration
- Hash verification of email addresses
- User identity verification
- Rate limiting support
- Audit logging capabilities

### 🎯 Framework Integration
- Works with any Laravel starter kit
- Automatic detection via model observers
- Manual integration options
- Livewire support with browser events
- Session-based fallback notifications

### ⚡ Performance
- Efficient database queries
- Minimal overhead when not in use
- Configurable rate limiting
- Optimized for high-traffic applications

### 🔧 Extensibility
- Override any component
- Custom controllers
- Custom notifications
- Custom services
- Event-driven architecture

### 📱 User Experience
- Mobile-friendly emails
- Clear confirmation process
- Helpful error messages
- Progress indicators
- Accessibility support

## Integration Points

### With Laravel Authentication
- Integrates with `MustVerifyEmail` interface
- Works with any authentication guard
- Supports custom user models
- Handles email verification flow

### With Mail System
- Uses Laravel's notification system
- Supports all mail drivers
- Customizable email templates
- Queue support for performance

### With Livewire
- Automatic browser event dispatch
- Session-based fallback
- Real-time UI updates
- Component integration examples

### With Validation
- Form request validation
- Custom validation rules
- Error message customization
- Multi-language support

## Customization Points

### 1. Controller Extension
Extend `EmailChangeController` to customize:
- Success/error handling
- Redirect destinations
- Additional security checks
- Logging and monitoring

### 2. Notification Customization
Extend `EmailChangeConfirmation` to customize:
- Email content and styling
- Additional notification channels
- Localization
- Branding

### 3. Service Extension
Extend `EmailChangeService` to add:
- Custom validation logic
- Rate limiting
- Integration with external services
- Business rule enforcement

### 4. Model Extension
Extend `EmailChange` model to add:
- Additional fields
- Custom relationships
- Business logic methods
- Audit trail functionality

## Testing Strategy

The package includes comprehensive tests covering:
- Service functionality
- Model behavior
- Controller responses
- Notification sending
- Integration scenarios

### Test Categories
1. **Unit Tests** - Individual component testing
2. **Integration Tests** - Component interaction testing
3. **Feature Tests** - End-to-end workflow testing
4. **Browser Tests** - UI interaction testing (optional)

## Deployment Considerations

### Database
- Migration handles different user ID types
- Indexes for performance
- Foreign key constraints
- Configurable table names

### Caching
- Route caching compatible
- Config caching compatible
- View caching compatible
- Event caching compatible

### Queues
- Notification queuing support
- Background processing ready
- Failure handling
- Retry mechanisms

### Monitoring
- Comprehensive logging
- Error tracking integration
- Performance monitoring hooks
- Security event logging

## Version Compatibility

### Laravel Versions
- Laravel 10.x: ✅ Fully supported
- Laravel 11.x: ✅ Fully supported
- Laravel 12.x: ✅ Fully supported
- Laravel 9.x: ⚠️ May work but not tested

### PHP Versions
- PHP 8.1: ✅ Minimum requirement
- PHP 8.2: ✅ Fully supported
- PHP 8.3: ✅ Fully supported

### Dependencies
- Minimal dependencies
- Uses Laravel core components
- Optional Livewire integration
- No external API dependencies

This package structure ensures maximum flexibility while maintaining simplicity and security. Each component is designed to be independently customizable while working seamlessly together.