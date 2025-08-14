<?php

namespace MilenMk\LaravelEmailChangeConfirmation\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange;

class CleanupExpiredEmailChanges implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $expireMinutes = config('email-change-confirmation.confirmation_email_expire_minutes', 60);
        $cutoffTime = Carbon::now()->subMinutes($expireMinutes);

        // Find all pending email changes that are older than the expiration time
        $expiredChanges = EmailChange::pending()
            ->where('created_at', '<', $cutoffTime)
            ->get();

        $deniedCount = 0;

        foreach ($expiredChanges as $emailChange) {
            try {
                $emailChange->deny();
                $deniedCount++;
            } catch (Exception $e) {
                Log::error('Failed to deny expired email change', [
                    'email_change_id' => $emailChange->id,
                    'user_id' => $emailChange->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($deniedCount > 0) {
            Log::info("Automatically denied {$deniedCount} expired email change requests");
        }
    }
}
