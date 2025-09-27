<?php

declare(strict_types=1);

namespace DonTeeWhy\LogStack\Tests\Unit;

use DonTeeWhy\LogStack\Formatters\LogStackFormatter;
use DonTeeWhy\LogStack\Handlers\LogStackHandler;
use DonTeeWhy\LogStack\Http\LogStackClient;
use DonTeeWhy\LogStack\Jobs\ProcessLogBatch;
use DonTeeWhy\LogStack\Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Monolog\Level;
use Monolog\LogRecord;

class HandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_handler_buffers_log_records(): void
    {
        $client = Mockery::mock(LogStackClient::class);
        $formatter = Mockery::mock(LogStackFormatter::class);
        
        $formatter->shouldReceive('format')
            ->times(2)
            ->andReturn('{"test": "formatted"}');
        
        // Should not call client->ingest until batch size reached
        $client->shouldNotReceive('ingest');
        
        $handler = new LogStackHandler(
            client: $client,
            formatter: $formatter,
            batchSize: 5,
            batchTimeoutMs: 5000,
            queueConnection: 'sync',
            async: false
        );
        
        // Add 2 records (less than batch size of 5)
        $record1 = $this->createLogRecord('First message');
        $record2 = $this->createLogRecord('Second message');
        
        $handler->handle($record1);
        $handler->handle($record2);
        
        // Verify no ingestion happened yet
        Queue::assertNotPushed(ProcessLogBatch::class);
        $this->assertTrue(true); // Add assertion to avoid risky test
        Mockery::close();
    }

    public function test_handler_flushes_when_batch_size_reached(): void
    {
        $client = Mockery::mock(LogStackClient::class);
        $formatter = new LogStackFormatter('test-service', 'testing', []);
        
        // Should call ingest once when batch size (3) is reached
        $client->shouldReceive('ingest')
            ->once()
            ->with(Mockery::on(function ($entries) {
                return count($entries) === 3
                    && $entries[0]['message'] === 'Message 0'
                    && $entries[1]['message'] === 'Message 1'
                    && $entries[2]['message'] === 'Message 2'
                    && $entries[0]['service'] === 'test-service'
                    && $entries[0]['env'] === 'testing';
            }));
        
        $handler = new LogStackHandler(
            client: $client,
            formatter: $formatter,
            batchSize: 3,
            batchTimeoutMs: 5000,
            queueConnection: 'sync',
            async: false  // SYNC MODE - calls client directly
        );
        
        // Add exactly batch size records - should trigger flush on 3rd record
        for ($i = 0; $i < 3; $i++) {
            $record = $this->createLogRecord("Message $i");
            $handler->handle($record);
        }
        
        // In sync mode, should NOT push to queue
        Queue::assertNothingPushed();
        
        Mockery::close();
    }

    public function test_handler_sync_mode_calls_client_directly(): void
    {
        $client = Mockery::mock(LogStackClient::class);
        $formatter = Mockery::mock(LogStackFormatter::class);
        
        $formatter->shouldReceive('format')
            ->once()
            ->andReturn('{"message": "test"}');
        
        $client->shouldReceive('ingest')
            ->once()
            ->with([['message' => 'test']]);
        
        $handler = new LogStackHandler(
            client: $client,
            formatter: $formatter,
            batchSize: 1,
            batchTimeoutMs: 5000,
            queueConnection: 'sync',
            async: false  // Sync mode
        );
        
        $record = $this->createLogRecord('Test message');
        $handler->handle($record);
        
        // Verify no queue jobs were dispatched in sync mode
        Queue::assertNothingPushed();
        $this->assertTrue(true); // Add assertion to avoid risky test
        
        Mockery::close();
    }

    public function test_handler_async_mode_dispatches_job(): void
    {
        $client = Mockery::mock(LogStackClient::class);
        $formatter = new LogStackFormatter('test-service', 'testing', []);
        
        // Should not call client directly in async mode
        $client->shouldNotReceive('ingest');
        
        $handler = new LogStackHandler(
            client: $client,
            formatter: $formatter,
            batchSize: 1,
            batchTimeoutMs: 5000,
            queueConnection: 'test-connection',
            async: true  // Async mode
        );
        
        $record = $this->createLogRecord('Test message');
        $handler->handle($record);
        
        // Verify job was dispatched with correct data
        Queue::assertPushed(ProcessLogBatch::class, function ($job) {
            return $job->connection === 'test-connection'
                && count($job->entries) === 1
                && $job->entries[0]['message'] === 'Test message'
                && $job->entries[0]['service'] === 'test-service';
        });
        
        Mockery::close();
    }

    public function test_manual_flush_empties_buffer(): void
    {
        $client = Mockery::mock(LogStackClient::class);
        $formatter = Mockery::mock(LogStackFormatter::class);
        
        $formatter->shouldReceive('format')
            ->times(2)
            ->andReturn('{"test": "formatted"}');
        
        $client->shouldReceive('ingest')
            ->once()
            ->with(Mockery::type('array'));
        
        $handler = new LogStackHandler(
            client: $client,
            formatter: $formatter,
            batchSize: 10, // Large batch size
            batchTimeoutMs: 5000,
            queueConnection: 'sync',
            async: false
        );
        
        // Add records without reaching batch size
        $handler->handle($this->createLogRecord('Message 1'));
        $handler->handle($this->createLogRecord('Message 2'));
        
        // Manually flush
        $handler->flush();
        
        Queue::assertNothingPushed(); // Sync mode
        $this->assertTrue(true); // Add assertion to avoid risky test
        
        Mockery::close();
    }

    public function test_flush_does_nothing_when_buffer_empty(): void
    {
        $client = Mockery::mock(LogStackClient::class);
        $formatter = Mockery::mock(LogStackFormatter::class);
        
        // Should not call anything when buffer is empty
        $client->shouldNotReceive('ingest');
        
        $handler = new LogStackHandler(
            client: $client,
            formatter: $formatter,
            batchSize: 10,
            batchTimeoutMs: 5000,
            queueConnection: 'sync',
            async: false
        );
        
        $handler->flush();
        
        Queue::assertNothingPushed(); // No jobs should be pushed
        $this->assertTrue(true); // Add assertion to avoid risky test
        
        Mockery::close();
    }

    public function test_handler_catches_and_logs_exceptions(): void
    {
        $client = Mockery::mock(LogStackClient::class);
        $formatter = Mockery::mock(LogStackFormatter::class);
        
        $formatter->shouldReceive('format')
            ->once()
            ->andReturn('{"test": "formatted"}');
        
        // Simulate client throwing exception
        $client->shouldReceive('ingest')
            ->once()
            ->andThrow(new \Exception('Network error'));
        
        $handler = new LogStackHandler(
            client: $client,
            formatter: $formatter,
            batchSize: 1,
            batchTimeoutMs: 5000,
            queueConnection: 'sync',
            async: false
        );
        
        // Should not throw exception - should catch and log it
        $record = $this->createLogRecord('Test message');
        $handler->handle($record);
        
        // Verify exception was caught and handled gracefully
        $this->assertTrue(true); // Add assertion to avoid risky test
        
        Mockery::close();
    }

    public function test_handler_clears_buffer_after_flush(): void
    {
        $client = Mockery::mock(LogStackClient::class);
        $formatter = Mockery::mock(LogStackFormatter::class);
        
        $formatter->shouldReceive('format')
            ->times(4)
            ->andReturn('{"test": "formatted"}');
        
        // Should be called twice - once for each batch
        $client->shouldReceive('ingest')
            ->twice()
            ->with(Mockery::type('array'));
        
        $handler = new LogStackHandler(
            client: $client,
            formatter: $formatter,
            batchSize: 2,
            batchTimeoutMs: 5000,
            queueConnection: 'sync',
            async: false
        );
        
        // First batch
        $handler->handle($this->createLogRecord('Message 1'));
        $handler->handle($this->createLogRecord('Message 2')); // Should flush
        
        // Second batch
        $handler->handle($this->createLogRecord('Message 3'));
        $handler->handle($this->createLogRecord('Message 4')); // Should flush again
        
        Queue::assertNothingPushed(); // Sync mode
        $this->assertTrue(true); // Add assertion to avoid risky test
        
        Mockery::close();
    }

    public function test_destructor_flushes_remaining_records(): void
    {
        $client = Mockery::mock(LogStackClient::class);
        $formatter = Mockery::mock(LogStackFormatter::class);
        
        $formatter->shouldReceive('format')
            ->once()
            ->andReturn('{"test": "formatted"}');
        
        $client->shouldReceive('ingest')
            ->once()
            ->with(Mockery::type('array'));
        
        $handler = new LogStackHandler(
            client: $client,
            formatter: $formatter,
            batchSize: 10, // Large batch size
            batchTimeoutMs: 5000,
            queueConnection: 'sync',
            async: false
        );
        
        // Add one record (won't trigger batch flush)
        $handler->handle($this->createLogRecord('Test message'));
        
        // Destroy handler - should trigger flush in destructor
        unset($handler);
        
        Queue::assertNothingPushed(); // Sync mode
        $this->assertTrue(true); // Add assertion to avoid risky test
        
        Mockery::close();
    }

    private function createLogRecord(string $message): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: $message,
            context: [],
            extra: []
        );
    }

    public function test_handler_flushes_on_timeout(): void
    {
        $client = Mockery::mock(LogStackClient::class);
        $formatter = new LogStackFormatter('test-service', 'testing', []);
        
        // Should call ingest when timeout check is triggered by second message
        $client->shouldReceive('ingest')
            ->once()
            ->with(Mockery::on(function ($entries) {
                // Should flush the first message due to timeout
                return count($entries) === 1
                    && $entries[0]['message'] === 'Timeout test message';
            }));
        
        $handler = new LogStackHandler(
            client: $client,
            formatter: $formatter,
            batchSize: 10, // Large batch size so batch won't trigger
            batchTimeoutMs: 100, // Very short timeout (100ms)
            queueConnection: 'sync',
            async: false
        );
        
        // Add one record (won't trigger batch flush)
        $record = $this->createLogRecord('Timeout test message');
        $handler->handle($record);
        
        // Wait for timeout to exceed
        usleep(150000); // 150ms > 100ms timeout
        
        // Add another record - this should trigger timeout flush
        $record2 = $this->createLogRecord('Second message');
        $handler->handle($record2);
        
        Queue::assertNothingPushed(); // Sync mode
        $this->assertTrue(true); // Add assertion to avoid risky test
        
        Mockery::close();
    }
 }
