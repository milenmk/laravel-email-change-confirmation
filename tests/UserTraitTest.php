<?php

namespace MilenMk\LaravelEmailChangeConfirmation\Tests;

use MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange;

class UserTraitTest extends TestCase
{
    /**
     * @test
     */
    public function has_email_changes_relationship()
    {
        $user = $this->createUser();

        EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'old@example.com',
            'new_email' => 'new1@example.com',
        ]);

        EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'old@example.com',
            'new_email' => 'new2@example.com',
        ]);

        $this->assertCount(2, $user->emailChanges);
    }

    /**
     * @test
     */
    public function can_get_pending_email_changes()
    {
        $user = $this->createUser();

        // Create pending change
        EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'old@example.com',
            'new_email' => 'pending@example.com',
        ]);

        // Create confirmed change
        EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'old@example.com',
            'new_email' => 'confirmed@example.com',
            'change_confirmed_at' => now(),
        ]);

        $pendingChanges = $user->pendingEmailChanges;

        $this->assertCount(1, $pendingChanges);
        $this->assertEquals('pending@example.com', $pendingChanges->first()->new_email);
    }

    /**
     * @test
     */
    public function can_check_if_user_has_pending_email_change()
    {
        $user = $this->createUser();

        $this->assertFalse($user->hasPendingEmailChange());

        EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'old@example.com',
            'new_email' => 'new@example.com',
        ]);

        $this->assertTrue($user->hasPendingEmailChange());
    }

    /**
     * @test
     */
    public function can_get_latest_pending_email_change()
    {
        $user = $this->createUser();

        $this->assertNull($user->getLatestPendingEmailChange());

        $firstChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'old@example.com',
            'new_email' => 'first@example.com',
        ]);

        sleep(1); // Ensure different timestamps

        $secondChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'old@example.com',
            'new_email' => 'second@example.com',
        ]);

        $latestChange = $user->getLatestPendingEmailChange();

        $this->assertNotNull($latestChange);
        $this->assertEquals('second@example.com', $latestChange->new_email);
        $this->assertEquals($secondChange->id, $latestChange->id);
    }

    /**
     * @test
     */
    public function can_check_if_user_can_request_email_change()
    {
        $user = $this->createUser();

        // Initially should be able to request
        $this->assertTrue($user->canRequestEmailChange());

        // Create a pending change
        EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'old@example.com',
            'new_email' => 'new@example.com',
        ]);

        // Should not be able to request another (default max is 1)
        $this->assertFalse($user->canRequestEmailChange());
    }

    /**
     * @test
     */
    public function returns_correct_email_for_verification()
    {
        $user = $this->createUser(['email' => 'current@example.com']);

        // Without pending change, should return current email
        $this->assertEquals('current@example.com', $user->getEmailForVerification());

        // With pending change, should still return current email
        EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'current@example.com',
            'new_email' => 'new@example.com',
        ]);

        $this->assertEquals('current@example.com', $user->getEmailForVerification());
    }

    /**
     * @test
     */
    public function has_latest_email_change_relationship()
    {
        $user = $this->createUser();

        $firstChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'old@example.com',
            'new_email' => 'first@example.com',
        ]);

        sleep(1); // Ensure different timestamps

        $secondChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'old@example.com',
            'new_email' => 'second@example.com',
        ]);

        $latestChange = $user->latestEmailChange;

        $this->assertNotNull($latestChange);
        $this->assertEquals($secondChange->id, $latestChange->id);
        $this->assertEquals('second@example.com', $latestChange->new_email);
    }
}
