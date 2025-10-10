<?php

declare(strict_types=1);

namespace DonTeeWhy\LogStack\Tests\Unit;

use DonTeeWhy\LogStack\Formatters\LogStackFormatter;
use DonTeeWhy\LogStack\Tests\TestCase;
use Monolog\Level;
use Monolog\LogRecord;

class FormatterTest extends TestCase
{
    private LogStackFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->formatter = new LogStackFormatter(
            serviceName: 'test-service',
            environment: 'testing',
            defaultLabels: ['region' => 'us-east-1', 'version' => 'v1.0']
        );
    }

    public function test_formats_basic_log_record(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable('2024-01-15 10:30:00'),
            channel: 'test',
            level: Level::Info,
            message: 'Test message',
            context: [],
            extra: []
        );

        $result = $this->formatter->format($record);
        $decoded = json_decode($result, true);

        $this->assertIsArray($decoded);
        $this->assertEquals('2024-01-15T10:30:00.000Z', $decoded['timestamp']);
        $this->assertEquals('INFO', $decoded['level']);
        $this->assertEquals('Test message', $decoded['message']);
        $this->assertEquals('test-service', $decoded['service']);
        $this->assertEquals('testing', $decoded['env']);
    }

    public function test_maps_log_levels_correctly(): void
    {
        $levelMappings = [
            ['level' => Level::Debug, 'expected' => 'DEBUG'],
            ['level' => Level::Info, 'expected' => 'INFO'],
            ['level' => Level::Notice, 'expected' => 'INFO'],
            ['level' => Level::Warning, 'expected' => 'WARN'],
            ['level' => Level::Error, 'expected' => 'ERROR'],
            ['level' => Level::Critical, 'expected' => 'ERROR'],
            ['level' => Level::Alert, 'expected' => 'FATAL'],
            ['level' => Level::Emergency, 'expected' => 'FATAL'],
        ];

        foreach ($levelMappings as $mapping) {
            $record = new LogRecord(
                datetime: new \DateTimeImmutable(),
                channel: 'test',
                level: $mapping['level'],
                message: 'Test',
                context: [],
                extra: []
            );

            $result = $this->formatter->format($record);
            $decoded = json_decode($result, true);

            $this->assertEquals($mapping['expected'], $decoded['level'], 
                "Failed mapping {$mapping['level']->name} to {$mapping['expected']}");
        }
    }

    public function test_includes_default_labels(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Test',
            context: [],
            extra: []
        );

        $result = $this->formatter->format($record);
        $decoded = json_decode($result, true);

        $this->assertEquals([
            'region' => 'us-east-1',
            'version' => 'v1.0'
        ], $decoded['labels']);
    }

    public function test_extracts_labels_from_context(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Test',
            context: [
                'user_id' => 123,
                'region' => 'us-west-2',  // Should become label
                'tenant' => 'company-xyz', // Should become label
                'schema_version' => 'v2',  // Should become label
                'order_id' => 456,         // Should stay in metadata
            ],
            extra: []
        );

        $result = $this->formatter->format($record);
        $decoded = json_decode($result, true);

        // Check labels (default + extracted)
        $expectedLabels = [
            'region' => 'us-west-2',      // Extracted (overrides default)
            'version' => 'v1.0',          // Default
            'tenant' => 'company-xyz',    // Extracted
            'schema_version' => 'v2',     // Extracted
        ];
        $this->assertEquals($expectedLabels, $decoded['labels']);

        // Check metadata (remaining context + extra)
        $expectedMetadata = [
            'user_id' => 123,
            'order_id' => 456,
        ];
        $this->assertEquals($expectedMetadata, $decoded['metadata']);
    }

    public function test_limits_label_values_to_64_chars(): void
    {
        $longValue = str_repeat('a', 100);
        
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Test',
            context: ['tenant' => $longValue],
            extra: []
        );

        $result = $this->formatter->format($record);
        $decoded = json_decode($result, true);

        $this->assertEquals(64, strlen($decoded['labels']['tenant']));
        $this->assertEquals(str_repeat('a', 64), $decoded['labels']['tenant']);
    }

    public function test_limits_labels_to_6_total(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Test',
            context: [
                'region' => 'us-east-1',
                'tenant' => 'tenant1',
                'schema_version' => 'v1',
                'extra1' => 'value1',
                'extra2' => 'value2',
                'extra3' => 'value3',
                'extra4' => 'value4', // This should be dropped
            ],
            extra: []
        );

        $result = $this->formatter->format($record);
        $decoded = json_decode($result, true);

        $this->assertLessThanOrEqual(6, count($decoded['labels']));
    }

    public function test_includes_extra_in_metadata(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Test',
            context: ['context_key' => 'context_value'],
            extra: ['extra_key' => 'extra_value', 'memory_usage' => '50MB']
        );

        $result = $this->formatter->format($record);
        $decoded = json_decode($result, true);

        $expectedMetadata = [
            'context_key' => 'context_value',
            'extra_key' => 'extra_value',
            'memory_usage' => '50MB',
        ];
        $this->assertEquals($expectedMetadata, $decoded['metadata']);
    }

    public function test_limits_message_length(): void
    {
        $longMessage = str_repeat('Test message ', 1000); // Very long message
        
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: $longMessage,
            context: [],
            extra: []
        );

        $result = $this->formatter->format($record);
        $decoded = json_decode($result, true);

        $this->assertLessThanOrEqual(8192, strlen($decoded['message']));
        $this->assertStringEndsWith('...', $decoded['message']);
    }

    public function test_handles_json_safe_conversion(): void
    {
        $resource = fopen('php://memory', 'r');
        $object = new \stdClass();
        $object->property = 'value';
        
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Test',
            context: [
                'resource' => $resource,
                'object' => $object,
                'string' => 'normal string',
            ],
            extra: []
        );

        $result = $this->formatter->format($record);
        $decoded = json_decode($result, true);

        $this->assertEquals('[RESOURCE]', $decoded['metadata']['resource']);
        $this->assertEquals('[OBJECT]', $decoded['metadata']['object']);
        $this->assertEquals('normal string', $decoded['metadata']['string']);
        
        fclose($resource);
    }

    public function test_format_batch(): void
    {
        $records = [
            new LogRecord(
                datetime: new \DateTimeImmutable(),
                channel: 'test',
                level: Level::Info,
                message: 'First message',
                context: [],
                extra: []
            ),
            new LogRecord(
                datetime: new \DateTimeImmutable(),
                channel: 'test',
                level: Level::Error,
                message: 'Second message',
                context: [],
                extra: []
            ),
        ];

        $result = $this->formatter->formatBatch($records);
        $decoded = json_decode($result, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('entries', $decoded);
        $this->assertCount(2, $decoded['entries']);
        
        $this->assertEquals('First message', $decoded['entries'][0]['message']);
        $this->assertEquals('INFO', $decoded['entries'][0]['level']);
        
        $this->assertEquals('Second message', $decoded['entries'][1]['message']);
        $this->assertEquals('ERROR', $decoded['entries'][1]['level']);
    }

    public function test_returns_valid_json(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Test message with special chars: " \\ / \n \t',
            context: ['key' => 'value with "quotes"'],
            extra: []
        );

        $result = $this->formatter->format($record);
        
        $this->assertJson($result);
        
        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded);
    }

    public function test_empty_labels_returns_json_object_not_array(): void
    {
        // Create formatter with no default labels to ensure empty labels scenario
        $formatter = new LogStackFormatter(
            serviceName: 'test-service',
            environment: 'testing',
            defaultLabels: []
        );

        $record = new LogRecord(
            datetime: new \DateTimeImmutable('2024-01-15 10:30:00'),
            channel: 'test',
            level: Level::Info,
            message: 'Test message',
            context: [], // No labels in context
            extra: []
        );

        $result = $formatter->format($record);
        $decoded = json_decode($result, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('labels', $decoded);
        
        // The key test: empty labels should be {} (object) not [] (array)
        // In PHP, this means it should be an empty associative array that JSON encodes as {}
        $this->assertEquals([], $decoded['labels']);
        
        // Verify the raw JSON contains {} not []
        $this->assertStringContainsString('"labels":{}', $result);
        $this->assertStringNotContainsString('"labels":[]', $result);
    }
}
