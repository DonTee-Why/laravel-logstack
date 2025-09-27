<?php

declare(strict_types=1);

namespace DonTeeWhy\LogStack\Tests\Unit;

use DonTeeWhy\LogStack\Config\LogStackConfig;
use DonTeeWhy\LogStack\Contracts\LogStackConfigInterface;
use DonTeeWhy\LogStack\Tests\TestCase;
use InvalidArgumentException;

class ConfigTest extends TestCase
{
    private function getValidBaseConfig(): array
    {
        return [
            'url' => 'https://example.com',
            'token' => 'validtoken123',
        ];
    }

    public function test_implements_interface(): void
    {
        $config = new LogStackConfig($this->getValidConfig());
        
        $this->assertInstanceOf(LogStackConfigInterface::class, $config);
    }

    public function test_gets_url_successfully(): void
    {
        $config = new LogStackConfig(['url' => 'https://example.com', 'token' => 'validtoken123']);
        
        $this->assertEquals('https://example.com', $config->getUrl());
    }

    public function test_throws_exception_when_url_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('LogStack URL not configured');
        
        $config = new LogStackConfig([], [], true); // Skip validation
        $config->getUrl();
    }

    public function test_gets_token_successfully(): void
    {
        $config = new LogStackConfig(['token' => 'testtoken', 'url' => 'https://example.com']);
        
        $this->assertEquals('testtoken', $config->getToken());
    }

    public function test_throws_exception_when_token_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('LogStack token not configured');
        
        $config = new LogStackConfig([], [], true); // Skip validation
        $config->getToken();
    }

    public function test_gets_service_name_with_default(): void
    {
        $config = new LogStackConfig($this->getValidBaseConfig(), [], true);
        
        $this->assertEquals('laravel-logstack', $config->getServiceName());
    }

    public function test_gets_service_name_from_config(): void
    {
        $baseConfig = $this->getValidBaseConfig();
        $baseConfig['service_name'] = 'custom-service';
        $config = new LogStackConfig($baseConfig);
        
        $this->assertEquals('custom-service', $config->getServiceName());
    }

    public function test_gets_environment_from_config(): void
    {
        $baseConfig = $this->getValidBaseConfig();
        $baseConfig['environment'] = 'staging';
        $config = new LogStackConfig($baseConfig);
        
        $this->assertEquals('staging', $config->getEnvironment());
    }

    public function test_async_can_be_disabled(): void
    {
        $baseConfig = $this->getValidBaseConfig();
        $baseConfig['async'] = false;
        $config = new LogStackConfig($baseConfig);
        
        $this->assertFalse($config->isAsync());
    }

    public function test_batch_size_can_be_customized(): void
    {
        $baseConfig = $this->getValidBaseConfig();
        $baseConfig['batch_size'] = 100;
        $config = new LogStackConfig($baseConfig);
        
        $this->assertEquals(100, $config->getBatchSize());
    }

    public function test_default_labels_can_be_set(): void
    {
        $baseConfig = $this->getValidBaseConfig();
        $baseConfig['default_labels'] = ['test' => 'value'];
        $config = new LogStackConfig($baseConfig);
        
        $this->assertEquals(['test' => 'value'], $config->getDefaultLabels());
    }

    public function test_timeout_can_be_customized(): void
    {
        $baseConfig = $this->getValidBaseConfig();
        $baseConfig['timeout'] = 60;
        $config = new LogStackConfig($baseConfig);
        
        $this->assertEquals(60, $config->getTimeout());
    }

    public function test_retry_config_can_be_customized(): void
    {
        $baseConfig = $this->getValidBaseConfig();
        $baseConfig['retry_attempts'] = 5;
        $baseConfig['retry_delay_ms'] = [1000, 2000, 4000];
        $config = new LogStackConfig($baseConfig);
        
        $expected = [
            'attempts' => 5,
            'delay_ms' => [1000, 2000, 4000],
        ];
        $this->assertEquals($expected, $config->getRetryConfig());
    }

    public function test_queue_connection_defaults_to_default(): void
    {
        $baseConfig = $this->getValidBaseConfig(); // This doesn't have queue_connection set
        $config = new LogStackConfig($baseConfig); // Will get default value
        
        $this->assertEquals('default', $config->getQueueConnection());
    }

    public function test_queue_connection_can_be_customized(): void
    {
        $baseConfig = $this->getValidBaseConfig();
        $baseConfig['queue_connection'] = 'redis';
        $config = new LogStackConfig($baseConfig);

        $this->assertEquals('redis', $config->getQueueConnection());
    }

    public function test_validation_passes_with_valid_config(): void
    {
        $config = new LogStackConfig([
            'url' => 'https://example.com',
            'token' => 'validtoken123',
        ]);
        
        $this->assertInstanceOf(LogStackConfig::class, $config);
    }

    public function test_validation_fails_with_invalid_url(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('LogStack URL is not a valid URL');
        
        new LogStackConfig([
            'url' => 'not-a-url',
            'token' => 'validtoken123',
        ]);
    }

    public function test_validation_fails_with_invalid_token(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('LogStack token is not a valid token');
        
        new LogStackConfig([
            'url' => 'https://example.com',
            'token' => 'invalid-token-with-symbols!',
        ]);
    }

    public function test_validation_fails_without_https(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('LogStack URL must use http:// or https://');
        
        new LogStackConfig([
            'url' => 'ftp://example.com',
            'token' => 'validtoken123',
        ]);
    }
}