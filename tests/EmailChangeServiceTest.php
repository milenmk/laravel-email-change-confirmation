<?php

namespace MilenMk\LaravelEmailChangeConfirmation\Tests;

use Illuminate\Support\Facades\Notification;
use MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange;
use MilenMk\LaravelEmailChangeConfirmation\Notifications\EmailChangeConfirmation;
use MilenMk\LaravelEmailChangeConfirmation\Services\EmailChangeService;
class EmailChangeServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        Notification::fake();
    }

    public function testCanRequestEmailChange()
    {
        $user = $this->createUser([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $service = new EmailChangeService();
        $emailChange = $service->requestEmailChange($user, 'newemail@example.com');

        $this->assertInstanceOf(EmailChange::class, $emailChange);
        $this->assertEquals($user->id, $emailChange->user_id);
        $this->assertEquals('john@example.com', $emailChange->current_email);
        $this->assertEquals('newemail@example.com', $emailChange->new_email);
        $this->assertTrue($emailChange->isPending());

        // Assert notification was sent
        Notification::assertSentTo($user, EmailChangeConfirmation::class);
    }

    public function testCanConfirmEmailChange()
    {
        $user = $this->createUser([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $emailChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'john@example.com',
            'new_email' => 'newemail@example.com',
        ]);

        $service = new EmailChangeService();
        $result = $service->confirmEmailChange($emailChange);

        $this->assertTrue($result);
        $this->assertTrue($emailChange->fresh()->isConfirmed());
        
        // Check that user's email was updated
        $this->assertEquals('newemail@example.com', $user->fresh()->email);
    }

    public function testCanDenyEmailChange()
    {
        $user = $this->createUser([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $emailChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'john@example.com',
            'new_email' => 'newemail@example.com',
        ]);

        $service = new EmailChangeService();
        $result = $service->denyEmailChange($emailChange);

        $this->assertTrue($result);
        $this->assertTrue($emailChange->fresh()->isDenied());
        
        // Check that user's email was NOT updated
        $this->assertEquals('john@example.com', $user->fresh()->email);
    }

    public function testValidatesEmailChangeCorrectly()
    {
        $user = $this->createUser([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $service = new EmailChangeService();

        // Same email should be invalid
        $this->assertFalse($service->validateEmailChange($user, 'john@example.com'));

        // Different email should be valid
        $this->assertTrue($service->validateEmailChange($user, 'newemail@example.com'));
    }

    public function testCanGetPendingEmailChanges()
    {
        $user = $this->createUser([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Create pending email change
        EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'john@example.com',
            'new_email' => 'newemail@example.com',
        ]);

        // Create confirmed email change
        EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'john@example.com',
            'new_email' => 'another@example.com',
            'change_confirmed_at' => now(),
        ]);

        $service = new EmailChangeService();
        $pendingChanges = $service->getPendingEmailChanges($user);

        $this->assertCount(1, $pendingChanges);
        $this->assertEquals('newemail@example.com', $pendingChanges->first()->new_email);
    }

    public function testCanCancelPendingEmailChanges()
    {
        $user = $this->createUser([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Create multiple pending email changes
        EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'john@example.com',
            'new_email' => 'newemail1@example.com',
        ]);

        EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'john@example.com',
            'new_email' => 'newemail2@example.com',
        ]);

        $service = new EmailChangeService();
        $cancelled = $service->cancelPendingEmailChanges($user);

        $this->assertEquals(2, $cancelled);
        $this->assertCount(0, $service->getPendingEmailChanges($user));
    }
}