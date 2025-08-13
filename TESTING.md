# Testing Guide

This document explains how to run tests for the Laravel Email Change Confirmation package.

## Running Tests Standalone

Yes, you can run the tests of this package **by itself** without needing to add it to a full Laravel application. The
package uses **Orchestra Testbench** which provides a minimal Laravel environment for testing.

## Prerequisites

1. **PHP 8.2 or higher**
2. **Composer** installed
3. **SQLite extensions enabled** (for in-memory testing database):
   - `sqlite3` extension
   - `pdo_sqlite` extension (required for Laravel database operations)
4. **PHPUnit 10.0 or 11.0** (automatically installed via composer)

## Installation for Testing

Navigate to the package directory and install dependencies:

```bash
cd test-packages/milenmk/laravel-email-change-confirmation
composer install
```

## Running Tests

### Basic Test Run

```bash
# Run all tests
composer test

# Or directly with PHPUnit
vendor/bin/phpunit
```

### With Coverage Report

```bash
# Generate HTML coverage report
composer test-coverage

# Coverage report will be generated in ./coverage directory
```

### Specific Test Files

```bash
# Run specific test file
vendor/bin/phpunit tests/EmailChangeServiceTest.php

# Run specific test method
vendor/bin/phpunit --filter it_can_request_email_change
```

### Verbose Output

```bash
# Run with verbose output
vendor/bin/phpunit --verbose

# Run with debug information
vendor/bin/phpunit --debug
```

## Test Structure

The package includes several test files:

### 1. `EmailChangeServiceTest.php`

Tests the core service functionality:

- ✅ Email change requests
- ✅ Email change confirmations
- ✅ Email change denials
- ✅ Validation logic
- ✅ Pending changes management

### 2. `EmailChangeModelTest.php`

Tests the EmailChange model:

- ✅ Model creation and relationships
- ✅ Status checking methods
- ✅ Confirmation and denial actions
- ✅ Query scopes

### 3. `UserTraitTest.php`

Tests the HasEmailChangeConfirmation trait:

- ✅ Relationship methods
- ✅ Helper methods
- ✅ Email verification integration
- ✅ Pending change management

### 4. `TestCase.php`

Base test class that:

- ✅ Sets up Orchestra Testbench environment
- ✅ Creates in-memory SQLite database
- ✅ Runs package migrations
- ✅ Provides helper methods

### 5. `TestUser.php`

Test user model that:

- ✅ Implements required traits and interfaces
- ✅ Provides test-specific functionality
- ✅ Simulates real user model behavior

## Test Environment

The tests run in a completely isolated environment:

- **Database**: In-memory SQLite (no external database needed)
- **Mail**: Array driver (emails are captured, not sent)
- **Queue**: Sync driver (jobs run immediately)
- **Cache**: Array driver (in-memory caching)
- **Session**: Array driver (in-memory sessions)

## What Gets Tested

### ✅ Core Functionality

- Email change request creation
- Email change confirmation process
- Email change denial process
- Validation of email changes
- Notification sending

### ✅ Model Behavior

- EmailChange model CRUD operations
- Status checking (pending, confirmed, denied)
- Relationships with User model
- Query scopes and filtering

### ✅ User Integration

- Trait methods on User model
- Relationship queries
- Helper methods for checking status
- Email verification integration

### ✅ Service Layer

- Business logic validation
- Error handling
- Notification management
- Database operations

## Running Tests in Different Environments

### Local Development

```bash
# Standard test run
composer test

# With coverage
composer test-coverage

# Watch mode (if you have phpunit-watcher)
vendor/bin/phpunit-watcher watch
```

### CI/CD Pipeline

```bash
# For GitHub Actions, GitLab CI, etc.
vendor/bin/phpunit --coverage-clover=coverage.xml

# For Jenkins or other CI systems
vendor/bin/phpunit --log-junit=test-results.xml
```

### Docker Environment

```bash
# If running in Docker container
docker run --rm -v $(pwd):/app -w /app php:8.1-cli composer test
```

## Test Configuration

The tests are configured via `phpunit.xml`:

```xml
<!-- Key configuration points -->
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="MAIL_MAILER" value="array"/>
</php>
```

## Debugging Tests

### Enable Debug Mode

```bash
vendor/bin/phpunit --debug
```

### Add Debug Output in Tests

```php
// In your test methods
dump($variable); // Laravel's dump helper
var_dump($variable); // PHP's var_dump
$this->dump($collection); // PHPUnit's dump method
```

### Check Database State

```php
// In test methods, you can inspect the database
$this->assertDatabaseHas('email_changes', [
    'user_id' => $user->id,
    'new_email' => 'test@example.com'
]);
```

## Common Issues and Solutions

### Issue: "Class 'SQLite3' not found" or "could not find driver"

**Solution**: Install SQLite PHP extensions

```bash
# Ubuntu/Debian
sudo apt-get install php-sqlite3 php-pdo-sqlite

# macOS with Homebrew
brew install php@8.2 --with-sqlite3

# Windows: Enable in php.ini
extension=sqlite3
extension=pdo_sqlite

# Verify extensions are loaded
php -m | grep -i sqlite
```

**Required extensions:**
- `sqlite3` - SQLite3 database support
- `pdo_sqlite` - PDO driver for SQLite (required for Laravel)

### Issue: "Orchestra\Testbench not found"

**Solution**: Install dev dependencies

```bash
composer install --dev
```

### Issue: Tests fail with "Table doesn't exist"

**Solution**: Check that migrations are running properly in TestCase.php

### Issue: "Memory limit exceeded"

**Solution**: Increase PHP memory limit

```bash
php -d memory_limit=512M vendor/bin/phpunit
```

## Writing Additional Tests

If you want to add more tests:

1. **Create test file** in `tests/` directory
2. **Extend TestCase** class
3. **Use helper methods** like `$this->createUser()`
4. **Follow naming convention**: `SomethingTest.php`
5. **Use descriptive test method names**: `it_can_do_something()`
6. **Use both test annotations** for maximum compatibility

Example:

```php
<?php

namespace MilenMk\LaravelEmailChangeConfirmation\Tests;

class MyNewTest extends TestCase
{
    public function testCanDoSomethingSpecific()
    {
        $user = $this->createUser();
        
        // Your test logic here
        
        $this->assertTrue(true);
    }
}
```

## PHPUnit Test Method Support

The package uses **method naming convention** for maximum compatibility across ALL PHPUnit versions:

### Universal Approach (Used in Package)

```php
public function testCanDoSomething()
{
    // Test code - works with ALL PHPUnit versions
}
```

**Why this approach is best:**

- ✅ **Universal compatibility** - Works with PHPUnit 4.x through 11.x+
- ✅ **No annotations needed** - No `@test` docblocks or `#[Test]` attributes required
- ✅ **No deprecation warnings** - Future-proof approach
- ✅ **Cleaner code** - No extra annotations cluttering the code
- ✅ **IDE friendly** - Better autocomplete and navigation

### Alternative Approaches (Not Used)

**Legacy docblock approach:**

```php
/** @test */
public function it_can_do_something()
{
    // Works with older PHPUnit but may show deprecation warnings
}
```

**Modern attribute approach:**

```php
#[Test]
public function it_can_do_something()
{
    // Only works with PHPUnit 10.1+ - breaks older versions
}
```

**Mixed approach (problematic):**

```php
/** @test */
#[Test]
public function it_can_do_something()
{
    // Can cause conflicts and deprecation warnings
}
```

### Method Naming Convention

The package follows **camelCase** naming for test methods:

- ✅ `testCanRequestEmailChange()` - Clear, descriptive
- ✅ `testValidatesEmailChangeCorrectly()` - Action-focused
- ✅ `testHasUserRelationship()` - State-focused

This approach ensures **100% compatibility** with all PHPUnit versions without any annotations or attributes.

## Performance Testing

For performance testing:

```bash
# Run tests with timing information
vendor/bin/phpunit --verbose --debug

# Profile memory usage
php -d memory_limit=128M vendor/bin/phpunit
```

## Integration with Package Development

The tests are designed to:

- ✅ Run independently of any Laravel application
- ✅ Test all package functionality in isolation
- ✅ Provide confidence when making changes
- ✅ Serve as documentation for expected behavior
- ✅ Ensure compatibility across Laravel versions

This testing setup allows you to develop and test the package completely independently, then integrate it into Laravel
applications with confidence that it works correctly.
