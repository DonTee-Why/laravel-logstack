<?php

declare(strict_types=1);

namespace DonTeeWhy\LogStack\Formatters;

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
        $logEntry = [
            //2024-01-15T10:30:00.000Z
            'timestamp' => $record->datetime->format(format: 'Y-m-d\TH:i:s.v\Z'),
            'level' => $this->mapLevel(monologLevel: $record->level->getName()),
            'message' => $this->limitString(value: $record->message, maxLength: 8192),
            'service' => $this->serviceName,
            'env' => $this->environment,
        ];
        // 2. Extract labels and metadata
        $logEntry['labels'] = $this->extractLabels(context: $record->context);
        $logEntry['metadata'] = $this->ensureJsonSafe(
            array_merge($record->context, $record->extra)
        );
        return json_encode(value: $logEntry, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Format multiple log records for batch processing.
     */
    public function formatBatch(array $records): string
    {
        $formattedEntries = [];

        foreach ($records as $record) {
            $formattedEntries[] = json_decode(json: $this->format(record: $record), associative: true);
        }

        return json_encode(value: [
            'entries' => $formattedEntries
        ], flags: JSON_THROW_ON_ERROR);
    }

    private function mapLevel(string $monologLevel): string
    {
        return match ($monologLevel) {
            'DEBUG' => 'DEBUG',
            'INFO' => 'INFO',
            'NOTICE' => 'INFO',
            'WARNING' => 'WARN',
            'ERROR' => 'ERROR',
            'CRITICAL' => 'ERROR',
            'ALERT' => 'FATAL',
            'EMERGENCY' => 'FATAL',
            default => 'INFO'
        };
    }

    private function extractLabels(array &$context): array
    {
        $labels = $this->defaultLabels;
        $labelKeys = ['region', 'tenant', 'schema_version'];

        foreach ($labelKeys as $key) {
            if (isset($context[$key])) {
                $labels[$key] = substr(string: (string) $context[$key], offset: 0, length: 64);
                unset($context[$key]);
            }
        }

        return array_slice(array: $labels, offset: 0, length: 6, preserve_keys: true);
    }

    private function limitString(string $value, int $maxLength): string
    {
        return strlen(string: $value) > $maxLength
            ? substr(string: $value, offset: 0, length: $maxLength - 3) . '...'
            : $value;
    }

    private function ensureJsonSafe(array $data): array
    {
        foreach ($data as $key => &$value) {
            if (is_resource(value: $value)) {
                $value = '[RESOURCE]';
            } elseif (is_object(value: $value)) {
                $value = method_exists(object_or_class: $value, method: '__toString') ? (string) $value : '[OBJECT]';
            }
        }
        return $data;
    }
}
