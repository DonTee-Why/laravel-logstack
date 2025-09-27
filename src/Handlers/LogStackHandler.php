<?php

declare(strict_types=1);

namespace DonTeeWhy\LogStack\Handlers;

use DonTeeWhy\LogStack\Http\LogStackClient;
use DonTeeWhy\LogStack\Formatters\LogStackFormatter;
use DonTeeWhy\LogStack\Jobs\ProcessLogBatch;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Illuminate\Support\Facades\Log;

/**
 * Monolog handler for LogStack service.
 * 
 * Processes log records and sends them to LogStack service via HTTP.
 * Supports both sync and async (queue-based) processing.
 */
class LogStackHandler extends AbstractProcessingHandler
{
    private bool $async;
    private LogStackClient $client;
    private LogStackFormatter $logStackFormatter;
    private array $buffer = [];
    private int $batchSize = 50;
    private int $batchTimeoutMs = 5000;
    private string $queueConnection;
    private float $lastFlushTime;

    public function __construct(
        LogStackClient $client,
        LogStackFormatter $formatter,
        int $batchSize,
        int $batchTimeoutMs,
        string $queueConnection,
        bool $async = false,
        Level $level = Level::Debug,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);
        $this->client = $client;
        $this->logStackFormatter = $formatter;
        $this->async = $async;
        $this->batchSize = $batchSize;
        $this->batchTimeoutMs = $batchTimeoutMs;
        $this->queueConnection = $queueConnection;
        $this->lastFlushTime = microtime(true);
    }

    /**
     * Process a log record.
     * 
     * Transform the log record to LogStack format and send to service.
     */
    protected function write(LogRecord $record): void
    {
        if ($this->isTimeoutExceeded()) {
            $this->flush();
        }
        
        $formattedRecord = $this->logStackFormatter->format($record);
        $this->buffer[] = json_decode(json: $formattedRecord, associative: true);
        
        // Flush if batch size reached
        if (count($this->buffer) >= $this->batchSize) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        try {

            if (empty($this->buffer)) {
                return;
            }

            if ($this->async) {
                ProcessLogBatch::dispatch($this->buffer)
                    ->onConnection(connection: $this->queueConnection);
            } else {
                $this->client->ingest(entries: $this->buffer);
            }

            $this->buffer = [];
            $this->lastFlushTime = microtime(true); // Reset timer after successful flush
        } catch (\Throwable $th) {
            // Log::error('LogStackHandler flush failed', [
            //     'error' => $th->getMessage(),
            //     'trace' => $th->getTraceAsString(),
            // ]);

            if ($this->async) {
                try {
                    $this->client->ingest($this->buffer);
                } catch (\Throwable $fallbackError) {
                    // Log::error('LogStackHandler flush fallback failed', [
                    //     'error' => $fallbackError->getMessage(),
                    //     'trace' => $fallbackError->getTraceAsString(),
                    // ]);
                }
            }
        }
    }

    /**
     * Check if the batch timeout has been exceeded.
     */
    private function isTimeoutExceeded(): bool
    {
        if (empty($this->buffer)) {
            return false;
        }
        
        $currentTime = microtime(true);
        $elapsedMs = ($currentTime - $this->lastFlushTime) * 1000;
        
        
        return $elapsedMs >= $this->batchTimeoutMs;
    }

    public function __destruct()
    {
        $this->flush();
    }
}
