# Changelog

All notable changes to `laravel-email-change-confirmation` will be documented in this file.

## [1.0.0] - 2025-01-13

### Added
- Initial release
- Secure email change confirmation functionality
- Automatic email change detection using model observers
- Integration with Laravel's email verification system
- Support for both traditional controllers and Livewire components
- Configurable notification system
- Extensible architecture with customizable controllers, services, and notifications
- Comprehensive documentation and examples
- Support for different Laravel starter kits and custom applications
- Security features including signed URLs, hash verification, and rate limiting
- Beautiful, responsive confirmation emails
- Facade for easy access to package functionality
- Trait for User models with helpful methods
- Database migrations with flexible user model support

### Framework Support
- Laravel 10.x: Full support
- Laravel 11.x: Full support
- Laravel 12.x: Full support
- PHP 8.1, 8.2, 8.3, 8.4: Full support

### Testing
- Comprehensive test suite with Orchestra Testbench
- Standalone testing capability (no Laravel app required)
- Dual PHPUnit test method support:
  - Legacy `@test` docblock (PHPUnit 9.x compatibility)
  - Modern `#[Test]` attribute (PHPUnit 10.x+ compatibility)
- Test coverage for all major functionality
- In-memory SQLite testing database
- Automated test runners for Windows and Unix systems