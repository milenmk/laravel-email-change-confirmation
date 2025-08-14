<?php

namespace MilenMk\LaravelEmailChangeConfirmation\Console\Commands;

use Illuminate\Console\Command;
use MilenMk\LaravelEmailChangeConfirmation\Jobs\CleanupExpiredEmailChanges as CleanupJob;

class CleanupExpiredEmailChanges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email-change:cleanup-expired
                            {--queue : Dispatch the cleanup job to the queue instead of running synchronously}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired email change requests by marking them as denied';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Cleaning up expired email change requests...');

        if ($this->option('queue')) {
            CleanupJob::dispatch();
            $this->info('Cleanup job dispatched to queue.');
        } else {
            $job = new CleanupJob;
            $job->handle();
            $this->info('Cleanup completed.');
        }

        return Command::SUCCESS;
    }
}
