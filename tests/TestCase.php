<?php

declare(strict_types=1);

namespace DonTeeWhy\LogStack\Tests;

use DonTeeWhy\LogStack\Providers\LogStackServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LogStackServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Set up test environment with valid LogStack config
        $app['config']->set('logstack.url', 'https://test-logstack.com');
        $app['config']->set('logstack.token', 'testtokenvalidabc123'); // Valid token format
        $app['config']->set('logstack.service_name', 'test-service');
        $app['config']->set('logstack.environment', 'testing');
        $app['config']->set('logstack.async', false);
        $app['config']->set('logstack.batch_size', 10);
        $app['config']->set('logstack.batch_timeout_ms', 5000);
        $app['config']->set('logstack.queue_connection', 'default');
        $app['config']->set('logstack.default_labels', ['region' => 'test-region']);
        $app['config']->set('logstack.timeout', 30);
        $app['config']->set('logstack.retry_attempts', 3);
        $app['config']->set('logstack.retry_delay_ms', [1000, 2000, 3000]);
        
        // Set up logging channel for integration tests
        $app['config']->set('logging.channels.logstack', [
            'driver' => 'logstack',
            'level' => 'debug',
        ]);
    }

    protected function getValidConfig(): array
    {
        return [
            'url' => 'https://test-logstack.com',
            'token' => 'testtokenvalidabc123',
            'service_name' => 'test-service',
            'environment' => 'testing',
            'async' => false,
            'batch_size' => 10,
            'batch_timeout_ms' => 5000,
            'queue_connection' => 'default',
            'default_labels' => ['region' => 'test-region'],
            'timeout' => 30,
            'retry_attempts' => 3,
            'retry_delay_ms' => [1000, 2000, 3000],
        ];
    }
}
