<?php
declare(strict_types=1);

namespace DonTeeWhy\LogStack\Contracts;

/**
 * Interface for LogStack configuration providers.
 * 
 * Defines the contract for accessing LogStack configuration.
 * Enables different config sources and easier testing.
 */
interface LogStackConfigInterface
{
    /**
     * Get LogStack service URL.
     * 
     * @throws \InvalidArgumentException When URL is not configured
     */
    public function getUrl(): string;

    /**
     * Get authentication token.
     * 
     * @throws \InvalidArgumentException When token is not configured
     */
    public function getToken(): string;

    /**
     * Get service name for this application.
     */
    public function getServiceName(): string;

    /**
     * Get environment name.
     */
    public function getEnvironment(): string;

    /**
     * Check if async processing is enabled.
     */
    public function isAsync(): bool;

    /**
     * Get batch size for log entries.
     */
    public function getBatchSize(): int;

    /**
     * Get default labels for log entries.
     */
    public function getDefaultLabels(): array;

    /**
     * Get HTTP timeout in seconds.
     */
    public function getTimeout(): int;

    /**
     * Get retry configuration.
     * 
     * @return array Contains 'attempts' and 'delay_ms' keys
     */
    public function getRetryConfig(): array;

    /**
     * Get queue connection.
     */
    public function getQueueConnection(): string;

    /**
     * Validate that all required configuration is present.
     * 
     * @throws \InvalidArgumentException When required config is missing
     */
    public function validate(): void;
}
