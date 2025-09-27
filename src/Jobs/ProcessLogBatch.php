<?php

declare(strict_types=1);

namespace DonTeeWhy\LogStack\Jobs;

use DonTeeWhy\LogStack\Http\LogStackClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job for processing log batches asynchronously.
 * 
 * Handles batch log ingestion to LogStack service via queue system.
 * Includes retry logic and error handling.
 */
class ProcessLogBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * The number of seconds to wait before retrying a failed job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $entries
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(LogStackClient $client): void
    {
        try {
            $client->ingest($this->entries);
        } catch (\Throwable $e) {
            Log::error('ProcessLogBatch job failed', [
                'error' => $e->getMessage(),
                'entries_count' => count($this->entries),
                'attempt' => $this->attempts(),
            ]);
            
            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessLogBatch job permanently failed', [
            'error' => $exception->getMessage(),
            'entries_count' => count($this->entries),
            'max_attempts' => $this->tries,
        ]);

        // TODO: Implement Notification/alerting
    }
}
