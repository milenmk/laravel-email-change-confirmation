<?php

namespace MilenMk\LaravelEmailChangeConfirmation\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use MilenMk\LaravelEmailChangeConfirmation\Jobs\CleanupExpiredEmailChanges;
use MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange;
use MilenMk\LaravelEmailChangeConfirmation\Tests\TestCase;

class CleanupExpiredEmailChangesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_marks_expired_pending_email_changes_as_denied()
    {
        // Create a user
        $user = $this->createUser();

        // Create an expired pending email change (created 2 hours ago)
        $expiredChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => $user->email,
            'new_email' => 'new@example.com',
        ]);
        $expiredChange->created_at = Carbon::now()->subHours(2);
        $expiredChange->save();

        // Create a recent pending email change (created 30 minutes ago)
        $recentChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => $user->email,
            'new_email' => 'another@example.com',
        ]);
        $recentChange->created_at = Carbon::now()->subMinutes(30);
        $recentChange->save();

        // Set expiration to 60 minutes
        config(['email-change-confirmation.confirmation_email_expire_minutes' => 60]);

        // Run the cleanup job
        $job = new CleanupExpiredEmailChanges;
        $job->handle();

        // Refresh models from database
        $expiredChange->refresh();
        $recentChange->refresh();

        // Assert expired change is marked as denied
        $this->assertTrue($expiredChange->isDenied());
        $this->assertNotNull($expiredChange->change_denied_at);

        // Assert recent change is still pending
        $this->assertTrue($recentChange->isPending());
        $this->assertNull($recentChange->change_denied_at);
    }

    /**
     * @test
     */
    public function it_does_not_affect_already_confirmed_or_denied_changes()
    {
        // Create a user
        $user = $this->createUser();

        // Create an old confirmed email change
        $confirmedChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'old@example.com',
            'new_email' => $user->email,
            'change_confirmed_at' => Carbon::now()->subHours(3),
            'created_at' => Carbon::now()->subHours(4),
        ]);

        // Create an old denied email change
        $deniedChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => $user->email,
            'new_email' => 'denied@example.com',
            'change_denied_at' => Carbon::now()->subHours(3),
            'created_at' => Carbon::now()->subHours(4),
        ]);

        // Run the cleanup job
        $job = new CleanupExpiredEmailChanges;
        $job->handle();

        // Refresh models from database
        $confirmedChange->refresh();
        $deniedChange->refresh();

        // Assert statuses remain unchanged
        $this->assertTrue($confirmedChange->isConfirmed());
        $this->assertTrue($deniedChange->isDenied());
    }

    /**
     * @test
     */
    public function cleanup_command_can_be_run_synchronously()
    {
        $this->artisan('email-change:cleanup-expired')
            ->expectsOutput('Cleaning up expired email change requests...')
            ->expectsOutput('Cleanup completed.')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function cleanup_command_can_be_queued()
    {
        $this->artisan('email-change:cleanup-expired --queue')
            ->expectsOutput('Cleaning up expired email change requests...')
            ->expectsOutput('Cleanup job dispatched to queue.')
            ->assertExitCode(0);
    }
}
