<?php
declare(strict_types=1);

namespace DonTeeWhy\LogStack\Contracts;

/**
 * Interface for LogStack HTTP client implementations.
 * 
 * Defines the contract for communicating with LogStack service.
 * Enables different implementations and easy testing with mocks.
 */
interface LogStackClientInterface
{
    /**
     * Send log entries to LogStack service.
     * 
     * @param array $entries Array of log entries in LogStack format
     * @return array Response from LogStack service
     * @throws \Exception When ingestion fails
     */
    public function ingest(array $entries): array;

    /**
     * Test connectivity to LogStack service.
     * 
     * @return bool True if service is reachable and responding
     */
    public function ping(): bool;

    /**
     * Get the base URL of the LogStack service.
     */
    public function getBaseUrl(): string;

    /**
     * Check if the client is properly configured.
     * 
     * @return bool True if client has valid URL and token
     */
    public function isConfigured(): bool;
}
