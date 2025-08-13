<?php

namespace MilenMk\LaravelEmailChangeConfirmation\Tests;

use MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange;
use MilenMk\LaravelEmailChangeConfirmation\Services\EmailChangeService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class SecurityTest extends TestCase
{
    public function testCannotConfirmWithInvalidHash()
    {
        $user = $this->createUser();
        $emailChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => $user->email,
            'new_email' => 'new@example.com',
        ]);

        // Create a signed URL with invalid hash
        $url = \URL::temporarySignedRoute(
            'email-change-confirmation.confirm',
            now()->addHour(),
            [
                'id' => $user->id,
                'hash' => 'invalid-hash', // Invalid hash
                'email_change' => $emailChange->id,
            ]
        );

        $response = $this->actingAs($user)->get($url);
        // The request will be rejected by the EmailChangeRequest authorization
        // which returns 403, but Laravel might convert this to 404 in some cases
        $this->assertContains($response->getStatusCode(), [403, 404]);
    }

    public function testCannotConfirmForDifferentUser()
    {
        $user1 = $this->createUser(['email' => 'user1@example.com']);
        $user2 = $this->createUser(['email' => 'user2@example.com']);
        
        $emailChange = EmailChange::create([
            'user_id' => $user1->id,
            'current_email' => $user1->email,
            'new_email' => 'new@example.com',
        ]);

        // Create a signed URL for user1 but try to access as user2
        $url = \URL::temporarySignedRoute(
            'email-change-confirmation.confirm',
            now()->addHour(),
            [
                'id' => $user1->id, // Different user ID
                'hash' => hash('sha256', $user1->email),
                'email_change' => $emailChange->id,
            ]
        );

        $response = $this->actingAs($user2)->get($url);
        $response->assertStatus(403);
    }

    public function testBlockedDomainsAreRejected()
    {
        Config::set('email-change-confirmation.blocked_domains', ['tempmail.com', 'spam.com']);
        
        $user = $this->createUser();
        $service = new EmailChangeService();

        // Test blocked domain
        $this->assertFalse($service->validateEmailChange($user, 'test@tempmail.com'));
        $this->assertFalse($service->validateEmailChange($user, 'test@SPAM.COM')); // Case insensitive
        
        // Test allowed domain
        $this->assertTrue($service->validateEmailChange($user, 'test@gmail.com'));
    }

    public function testRateLimitingWorks()
    {
        Config::set('email-change-confirmation.max_requests_per_hour', 2);
        
        $user = $this->createUser();
        $service = new EmailChangeService();

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

    public function testHmacHashingWhenSecretConfigured()
    {
        Config::set('email-change-confirmation.hash_secret', 'test-secret-key');
        Config::set('email-change-confirmation.max_pending_changes_per_user', 5); // Allow multiple
        
        $user = $this->createUser();
        $service = new EmailChangeService();
        
        // Request should work with HMAC hashing
        $result = $service->requestEmailChange($user, 'test@example.com');
        $this->assertInstanceOf(EmailChange::class, $result);
    }

    public function testCannotReuseConfirmedEmailChange()
    {
        $user = $this->createUser();
        $emailChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => $user->email,
            'new_email' => 'new@example.com',
        ]);

        // Confirm the email change
        $emailChange->confirm();

        $service = new EmailChangeService();
        
        // Trying to confirm again should fail
        $result = $service->confirmEmailChange($emailChange);
        $this->assertFalse($result);
    }

    public function testCannotReuseDeniedEmailChange()
    {
        $user = $this->createUser();
        $emailChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => $user->email,
            'new_email' => 'new@example.com',
        ]);

        // Deny the email change
        $emailChange->deny();

        $service = new EmailChangeService();
        
        // Trying to confirm denied change should fail
        $result = $service->confirmEmailChange($emailChange);
        $this->assertFalse($result);
    }

    public function testSameEmailValidationWorks()
    {
        $user = $this->createUser(['email' => 'test@example.com']);
        $service = new EmailChangeService();

        // Trying to change to same email should fail
        $this->assertFalse($service->validateEmailChange($user, 'test@example.com'));
        
        // Different email should work
        $this->assertTrue($service->validateEmailChange($user, 'different@example.com'));
    }

    public function testMaxPendingChangesLimit()
    {
        Config::set('email-change-confirmation.max_pending_changes_per_user', 1);
        
        $user = $this->createUser();
        
        // Create first pending change
        EmailChange::create([
            'user_id' => $user->id,
            'current_email' => $user->email,
            'new_email' => 'test1@example.com',
        ]);

        $service = new EmailChangeService();
        
        // Should not be able to create another pending change
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User has reached the maximum number of pending email changes');
        
        $service->requestEmailChange($user, 'test2@example.com');
    }

    public function testHashSecretValidation()
    {
        Config::set('email-change-confirmation.max_pending_changes_per_user', 10); // Allow multiple
        
        // Test with no secret
        Config::set('email-change-confirmation.hash_secret', null);
        $user = $this->createUser();
        $service = new EmailChangeService();
        
        // Should still work but use weak hashing
        $result = $service->requestEmailChange($user, 'test@example.com');
        $this->assertInstanceOf(EmailChange::class, $result);
        
        // Test with short secret (should work but log warning)
        Config::set('email-change-confirmation.hash_secret', 'short');
        $result2 = $service->requestEmailChange($user, 'test2@example.com');
        $this->assertInstanceOf(EmailChange::class, $result2);
        
        // Test with good secret
        Config::set('email-change-confirmation.hash_secret', 'this-is-a-very-secure-32-character-secret-key-for-testing');
        $result3 = $service->requestEmailChange($user, 'test3@example.com');
        $this->assertInstanceOf(EmailChange::class, $result3);
    }

    public function testHashSecretStrengthValidation()
    {
        $serviceProvider = new \MilenMk\LaravelEmailChangeConfirmation\EmailChangeConfirmationServiceProvider(app());
        $reflection = new \ReflectionClass($serviceProvider);
        $method = $reflection->getMethod('isWeakHashSecret');
        $method->setAccessible(true);
        
        // Test weak secrets
        $this->assertTrue($method->invoke($serviceProvider, 'secret123'));
        $this->assertTrue($method->invoke($serviceProvider, 'password'));
        $this->assertTrue($method->invoke($serviceProvider, 'test_key'));
        $this->assertTrue($method->invoke($serviceProvider, 'aaaaaaaaaa')); // Repeated chars
        
        // Test strong secrets
        $this->assertFalse($method->invoke($serviceProvider, 'YWJjZGVmZ2hpams1bG1ub3BxcnN0dXZ3eHl6QUJDREVGR0hJSktMTU5PUFFSU1RVVldYWVo='));
        $this->assertFalse($method->invoke($serviceProvider, 'random-string-with-good-entropy-2024-xyz'));
    }
}