<?php
declare(strict_types=1);

namespace DonTeeWhy\LogStack\Formatters;

use Illuminate\Support\Carbon;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

/**
 * Formatter for LogStack service.
 * 
 * Transforms Monolog LogRecord objects into LogStack-compatible format.
 * Handles field mapping, data sanitization, and JSON serialization.
 */
final class LogStackFormatter implements FormatterInterface
{
    private string $serviceName;
    private string $environment;
    private array $defaultLabels;

    public function __construct(
        string $serviceName,
        string $environment,
        array $defaultLabels = []
    ) {
        $this->serviceName = $serviceName;
        $this->environment = $environment;
        $this->defaultLabels = $defaultLabels;
    }

    /**
     * Format a log record for LogStack service.
     */
    public function format(LogRecord $record): string
    {
        // TODO: Implement formatting logic
        // 1. Map Monolog fields to LogStack format
        $logStackRecord = [
            //2024-01-15T10:30:00.000Z
            'timestamp' => Carbon::parse($record->datetime)->format('Y-m-d\TH:i:s.uP'),
            'level' => $record->level,
            'message' => $record->message,
            'service' => $this->serviceName,
            'env' => $this->environment,
        ];
        // 2. Extract labels and metadata
        // 3. Apply data sanitization
        // 4. Return JSON string
        
        return json_encode($logStackRecord, JSON_THROW_ON_ERROR);
    }

    /**
     * Format multiple log records for batch processing.
     */
    public function formatBatch(array $records): string
    {
        // TODO: Implement batch formatting
        // 1. Format each record
        // 2. Wrap in LogStack batch format
        // 3. Return JSON string
        
        throw new \RuntimeException('LogStackFormatter formatBatch not implemented yet');
    }
}
