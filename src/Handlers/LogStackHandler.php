<?php
declare(strict_types=1);

namespace DonTeeWhy\LogStack\Handlers;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

/**
 * Monolog handler for LogStack service.
 * 
 * Processes log records and sends them to LogStack service via HTTP.
 * Supports both sync and async (queue-based) processing.
 */
final class LogStackHandler extends AbstractProcessingHandler
{
    /**
     * Process a log record.
     * 
     * Transform the log record to LogStack format and send to service.
     */
    protected function write(LogRecord $record): void
    {
        // TODO: Implement handler logic
        // 1. Transform LogRecord to LogStack format
        // 2. Send to LogStack service (sync/async)
        // 3. Handle errors and fallbacks
        
        throw new \RuntimeException('LogStackHandler not implemented yet');
    }
}
