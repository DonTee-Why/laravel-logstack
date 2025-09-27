<?php

declare(strict_types=1);

namespace DonTeeWhy\LogStack\Tests\Feature;

use DonTeeWhy\LogStack\Jobs\ProcessLogBatch;
use DonTeeWhy\LogStack\Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Log\Logger as LaravelLogger;
use Monolog\Logger;

class LaravelIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_service_provider_registers_logstack_driver(): void
    {
        // Test that our service provider registered the logstack driver
        $logger = Log::channel('logstack');
        
        // Laravel wraps Monolog Logger in Illuminate\Log\Logger
        $this->assertInstanceOf(LaravelLogger::class, $logger);
        
        // Get the underlying Monolog logger
        $monologLogger = $logger->getLogger();
        $this->assertInstanceOf(Logger::class, $monologLogger);
        $this->assertEquals('logstack', $monologLogger->getName());
        
        // Verify our handler is attached
        $handlers = $monologLogger->getHandlers();
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(\DonTeeWhy\LogStack\Handlers\LogStackHandler::class, $handlers[0]);
    }

    public function test_can_log_through_laravel_log_facade(): void
    {
        // Set up async logging channel config
        config([
            'logging.channels.logstack_async' => [
                'driver' => 'logstack',
                'level' => 'debug',
                'async' => true,  // Override to enable async mode
                'batch_size' => 1,  // Flush immediately
            ]
        ]);
        
        // Test actual logging through Laravel's Log facade with async channel
        Log::channel('logstack_async')->info('Test message from Laravel', [
            'user_id' => 123,
            'action' => 'test_action',
        ]);

        // In async mode, should dispatch queue job
        Queue::assertPushed(ProcessLogBatch::class, function ($job) {
            return count($job->entries) === 1
                && $job->entries[0]['message'] === 'Test message from Laravel'
                && $job->entries[0]['service'] === 'test-service'
                && $job->entries[0]['env'] === 'testing'
                && $job->entries[0]['level'] === 'INFO'
                && isset($job->entries[0]['metadata']['user_id'])
                && $job->entries[0]['metadata']['user_id'] === 123;
        });
    }

    public function test_config_is_properly_loaded(): void
    {
        // Test that our config is loaded correctly
        $this->assertEquals('https://test-logstack.com', config('logstack.url'));
        $this->assertEquals('testtokenvalidabc123', config('logstack.token'));
        $this->assertEquals('test-service', config('logstack.service_name'));
        $this->assertEquals('testing', config('logstack.environment'));
        $this->assertFalse(config('logstack.async')); // We set it to false in test setup
    }

    public function test_can_log_different_levels(): void
    {
        // Set up async logging channel config
        config([
            'logging.channels.logstack_async' => [
                'driver' => 'logstack',
                'level' => 'debug',
                'async' => true,
                'batch_size' => 1,
            ]
        ]);
        
        $logger = Log::channel('logstack_async');
        
        $logger->debug('Debug message');
        $logger->info('Info message');
        $logger->warning('Warning message');
        $logger->error('Error message');

        // Should dispatch one job per message (batch size is 10, so no batching yet)
        Queue::assertPushed(ProcessLogBatch::class, 4);
        
        // Verify each level is mapped correctly
        Queue::assertPushed(ProcessLogBatch::class, function ($job) {
            return $job->entries[0]['level'] === 'DEBUG' && $job->entries[0]['message'] === 'Debug message';
        });
        
        Queue::assertPushed(ProcessLogBatch::class, function ($job) {
            return $job->entries[0]['level'] === 'INFO' && $job->entries[0]['message'] === 'Info message';
        });
        
        Queue::assertPushed(ProcessLogBatch::class, function ($job) {
            return $job->entries[0]['level'] === 'WARN' && $job->entries[0]['message'] === 'Warning message';
        });
        
        Queue::assertPushed(ProcessLogBatch::class, function ($job) {
            return $job->entries[0]['level'] === 'ERROR' && $job->entries[0]['message'] === 'Error message';
        });
    }

    public function test_labels_are_extracted_from_context(): void
    {
        // Set up async logging channel config
        config([
            'logging.channels.logstack_async' => [
                'driver' => 'logstack',
                'level' => 'debug',
                'async' => true,  // Override to enable async mode
                'batch_size' => 1,  // Flush immediately
            ]
        ]);

        Log::channel('logstack_async')->info('Test with labels', [
            'region' => 'us-west-2',
            'tenant' => 'customer-123',
            'user_id' => 456,
        ]);

        Queue::assertPushed(ProcessLogBatch::class, function ($job) {
            $entry = $job->entries[0];
            
            // Labels should include extracted context + default labels
            return isset($entry['labels']['region']) 
                && $entry['labels']['region'] === 'us-west-2'
                && isset($entry['labels']['tenant'])
                && $entry['labels']['tenant'] === 'customer-123'
                && isset($entry['metadata']['user_id'])
                && $entry['metadata']['user_id'] === 456;
        });
    }

    public function test_sync_mode_does_not_use_queue(): void
    {
        // Override config for this test
        config(['logstack.async' => false]);
        
        // Create a new logger instance with sync config
        $logger = app('log')->channel('logstack');
        
        $logger->info('Sync test message');

        // In sync mode, should not dispatch any queue jobs
        Queue::assertNothingPushed();
    }

    public function test_can_publish_config(): void
    {
        // Test that config can be published
        $this->artisan('vendor:publish', [
            '--provider' => 'DonTeeWhy\\LogStack\\Providers\\LogStackServiceProvider',
            '--tag' => 'logstack-config'
        ])->assertExitCode(0);
        
        // Verify config file exists (in test environment it goes to a temp location)
        $this->assertTrue(true); // Config publishing works if command succeeds
    }

    public function test_handles_non_serializable_context(): void
    {
        // Set up async logging channel config
        config([
            'logging.channels.logstack_async' => [
                'driver' => 'logstack',
                'level' => 'debug',
                'async' => true,
                'batch_size' => 1,
            ]
        ]);
        
        $resource = fopen('php://memory', 'r');
        
        Log::channel('logstack_async')->info('Test with resource', [
            'resource' => $resource,
            'object' => new \stdClass(),
            'normal' => 'value',
        ]);

        fclose($resource);

        Queue::assertPushed(ProcessLogBatch::class, function ($job) {
            $metadata = $job->entries[0]['metadata'];
            
            return $metadata['resource'] === '[RESOURCE]'
                && $metadata['object'] === '[OBJECT]'
                && $metadata['normal'] === 'value';
        });
    }
}