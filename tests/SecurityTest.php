<?php

namespace MilenMk\LaravelEmailChangeConfirmation\Tests;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use MilenMk\LaravelEmailChangeConfirmation\EmailChangeConfirmationServiceProvider;
use MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange;
use MilenMk\LaravelEmailChangeConfirmation\Services\EmailChangeService;
use ReflectionClass;

class SecurityTest extends TestCase
{
    /**
     * @test
     */
    public function cannot_confirm_with_invalid_hash()
    {
        $user = $this->createUser();
        $emailChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => $user->email,
            'new_email' => 'new@example.com',
        ]);

        // Create a signed URL with invalid hash
        $url = URL::temporarySignedRoute('email-change-confirmation.confirm', now()->addHour(), [
            'id' => $user->id,
            'hash' => 'invalid-hash', // Invalid hash
            'email_change' => $emailChange->id,
        ]);

        $response = $this->actingAs($user)->get($url);
        // The request will be rejected by the EmailChangeRequest authorization
        // which returns 403, but Laravel might convert this to 404 in some cases
        $this->assertContains($response->getStatusCode(), [403, 404]);
    }

    /**
     * @test
     */
    public function cannot_confirm_for_different_user()
    {
        $user1 = $this->createUser(['email' => 'user1@example.com']);
        $user2 = $this->createUser(['email' => 'user2@example.com']);

        $emailChange = EmailChange::create([
            'user_id' => $user1->id,
            'current_email' => $user1->email,
            'new_email' => 'new@example.com',
        ]);

        // Create a signed URL for user1 but try to access as user2
        $url = URL::temporarySignedRoute('email-change-confirmation.confirm', now()->addHour(), [
            'id' => $user1->id, // Different user ID
            'hash' => hash('sha256', $user1->email),
            'email_change' => $emailChange->id,
        ]);

        $response = $this->actingAs($user2)->get($url);
        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function blocked_domains_are_rejected()
    {
        Config::set('email-change-confirmation.blocked_domains', ['tempmail.com', 'spam.com']);

        $user = $this->createUser();
        $service = new EmailChangeService;

        // Test blocked domain
        $this->assertFalse($service->validateEmailChange($user, 'test@tempmail.com'));
        $this->assertFalse($service->validateEmailChange($user, 'test@SPAM.COM')); // Case insensitive

        // Test allowed domain
        $this->assertTrue($service->validateEmailChange($user, 'test@gmail.com'));
    }

    /**
     * @test
     */
    public function rate_limiting_works()
    {
        Config::set('email-change-confirmation.max_requests_per_hour', 2);

        $user = $this->createUser();
        $service = new EmailChangeService;

        // Create first email change to simulate a request
        EmailChange::create([
            'user_id' => $user->id,
            'current_email' => $user->email,
            'new_email' => 'test1@example.com',
            'created_at' => now(),
        ]);

        // Create second email change
        EmailChange::create([
            'user_id' => $user->id,
            'current_email' => $user->email,
            'new_email' => 'test2@example.com',
            'created_at' => now(),
        ]);

        // Third request should be blocked by rate limiting
        $this->assertFalse($service->validateEmailChange($user, 'test3@example.com'));
    }

    /**
     * @test
     */
    public function hmac_hashing_when_secret_configured()
    {
        Config::set('email-change-confirmation.hash_secret', 'test-secret-key');
        Config::set('email-change-confirmation.max_pending_changes_per_user', 5); // Allow multiple

        $user = $this->createUser();
        $service = new EmailChangeService;

        // Request should work with HMAC hashing
        $result = $service->requestEmailChange($user, 'test@example.com');
        $this->assertInstanceOf(EmailChange::class, $result);
    }

    /**
     * @test
     */
    public function cannot_reuse_confirmed_email_change()
    {
        $user = $this->createUser();
        $emailChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => $user->email,
            'new_email' => 'new@example.com',
        ]);

        // Confirm the email change
        $emailChange->confirm();

        $service = new EmailChangeService;

        // Trying to confirm again should fail
        $result = $service->confirmEmailChange($emailChange);
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function cannot_reuse_denied_email_change()
    {
        $user = $this->createUser();
        $emailChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => $user->email,
            'new_email' => 'new@example.com',
        ]);

        // Deny the email change
        $emailChange->deny();

        $service = new EmailChangeService;

        // Trying to confirm denied change should fail
        $result = $service->confirmEmailChange($emailChange);
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function same_email_validation_works()
    {
        $user = $this->createUser(['email' => 'test@example.com']);
        $service = new EmailChangeService;

        // Trying to change to same email should fail
        $this->assertFalse($service->validateEmailChange($user, 'test@example.com'));

        // Different email should work
        $this->assertTrue($service->validateEmailChange($user, 'different@example.com'));
    }

    /**
     * @test
     */
    public function max_pending_changes_limit()
    {
        Config::set('email-change-confirmation.max_pending_changes_per_user', 1);

        $user = $this->createUser();

        // Create first pending change
        EmailChange::create([
            'user_id' => $user->id,
            'current_email' => $user->email,
            'new_email' => 'test1@example.com',
        ]);

        $service = new EmailChangeService;

        // Should not be able to create another pending change
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User has reached the maximum number of pending email changes');

        $service->requestEmailChange($user, 'test2@example.com');
    }

    /**
     * @test
     */
    public function hash_secret_validation()
    {
        Config::set('email-change-confirmation.max_pending_changes_per_user', 10); // Allow multiple

        // Test with no secret
        Config::set('email-change-confirmation.hash_secret', null);
        $user = $this->createUser();
        $service = new EmailChangeService;

        // Should still work but use weak hashing
        $result = $service->requestEmailChange($user, 'test@example.com');
        $this->assertInstanceOf(EmailChange::class, $result);

        // Test with short secret (should work but log warning)
        Config::set('email-change-confirmation.hash_secret', 'short');
        $result2 = $service->requestEmailChange($user, 'test2@example.com');
        $this->assertInstanceOf(EmailChange::class, $result2);

        // Test with good secret
        Config::set(
            'email-change-confirmation.hash_secret',
            'this-is-a-very-secure-32-character-secret-key-for-testing',
        );
        $result3 = $service->requestEmailChange($user, 'test3@example.com');
        $this->assertInstanceOf(EmailChange::class, $result3);
    }

    /**
     * @test
     */
    public function hash_secret_strength_validation()
    {
        $serviceProvider = new EmailChangeConfirmationServiceProvider(app());
        $reflection = new ReflectionClass($serviceProvider);
        $method = $reflection->getMethod('isWeakHashSecret');
        $method->setAccessible(true);

        // Test weak secrets
        $this->assertTrue($method->invoke($serviceProvider, 'secret123'));
        $this->assertTrue($method->invoke($serviceProvider, 'password'));
        $this->assertTrue($method->invoke($serviceProvider, 'test_key'));
        $this->assertTrue($method->invoke($serviceProvider, 'aaaaaaaaaa')); // Repeated chars

        // Test strong secrets
        $this->assertFalse(
            $method->invoke(
                $serviceProvider,
                'YWJjZGVmZ2hpams1bG1ub3BxcnN0dXZ3eHl6QUJDREVGR0hJSktMTU5PUFFSU1RVVldYWVo=',
            ),
        );
        $this->assertFalse($method->invoke($serviceProvider, 'random-string-with-good-entropy-2024-xyz'));
    }
}
