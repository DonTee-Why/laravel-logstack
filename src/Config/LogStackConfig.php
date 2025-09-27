<?php

declare(strict_types=1);

namespace DonTeeWhy\LogStack\Config;

use DonTeeWhy\LogStack\Contracts\LogStackConfigInterface;

/**
 * Configuration management for LogStack package.
 * 
 * Provides centralized access to LogStack configuration with validation
 * and default values.
 */
class LogStackConfig implements LogStackConfigInterface
{
    private array $config;

    public function __construct(array $config = [], ?array $laravelConfig = null, bool $skipValidation = false)
    {
        // Merge provided config with Laravel config (if provided)
        if ($laravelConfig !== null) {
            $this->config = array_merge($laravelConfig, $config);
        } else {
            $this->config = $config;
        }

        if (!$skipValidation) {
            $this->validate();
        }
    }

    /**
     * Get LogStack service URL.
     */
    public function getUrl(): string
    {
        return $this->config['url'] ?? throw new \InvalidArgumentException('LogStack URL not configured');
    }

    /**
     * Get authentication token.
     */
    public function getToken(): string
    {
        return $this->config['token'] ?? throw new \InvalidArgumentException('LogStack token not configured');
    }

    /**
     * Get service name for this application.
     */
    public function getServiceName(): string
    {
        return $this->config['service_name'] ?? 'laravel-logstack';
    }

    /**
     * Get environment name.
     */
    public function getEnvironment(): string
    {
        return $this->config['environment'] ?? app()->environment() ?? 'production';
    }

    /**
     * Check if async processing is enabled.
     */
    public function isAsync(): bool
    {
        return $this->config['async'] ?? true;
    }

    /**
     * Get batch size for log entries.
     */
    public function getBatchSize(): int
    {
        return $this->config['batch_size'] ?? 50;
    }

    /**
     * Get default labels for log entries.
     */
    public function getDefaultLabels(): array
    {
        return $this->config['default_labels'] ?? [];
    }

    /**
     * Get HTTP timeout in seconds.
     */
    public function getTimeout(): int
    {
        return $this->config['timeout'] ?? 30;
    }

    /**
     * Get retry configuration.
     */
    public function getRetryConfig(): array
    {
        return [
            'attempts' => $this->config['retry_attempts'] ?? 3,
            'delay_ms' => $this->config['retry_delay_ms'] ?? [5000, 10000, 20000],
        ];
    }

    public function getQueueConnection(): string
    {
        return $this->config['queue_connection'] ?? 'default';
    }

    /**
     * Validate required configuration.
     */
    public function validate(): void
    {
        if (empty($this->config['url'])) {
            throw new \InvalidArgumentException('LogStack URL not configured');
        }
        if (empty($this->config['token'])) {
            throw new \InvalidArgumentException('LogStack token not configured');
        }
        if (!filter_var($this->config['url'], FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('LogStack URL is not a valid URL');
        }
        if (!str_starts_with($this->config['url'], 'http://') && !str_starts_with($this->config['url'], 'https://')) {
            throw new \InvalidArgumentException('LogStack URL must use http:// or https://');
        }
        if (!filter_var($this->config['token'], FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^[a-zA-Z0-9]+$/']])) {
            throw new \InvalidArgumentException('LogStack token is not a valid token');
        }
    }
}
