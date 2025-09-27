<?php
declare(strict_types=1);

namespace DonTeeWhy\LogStack\Http;

use GuzzleHttp\Client;

/**
 * HTTP client for LogStack service.
 * 
 * Handles communication with LogStack API including:
 * - Authentication with bearer tokens
 * - Retry logic with exponential backoff
 * - Circuit breaker pattern
 * - Batch log ingestion
 */
final class LogStackClient
{
    private Client $httpClient;
    private string $baseUrl;
    private string $token;

    public function __construct(
        string $baseUrl,
        string $token,
        array $httpOptions = []
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;
        
        // TODO: Configure Guzzle client with retry middleware, timeouts, etc.
        $this->httpClient = new Client($httpOptions);
    }

    /**
     * Send log entries to LogStack service.
     * 
     * @param array $entries Array of log entries in LogStack format
     * @return array Response from LogStack service
     */
    public function ingest(array $entries): array
    {
        // TODO: Implement ingestion logic
        // 1. Prepare request payload
        // 2. Send POST to /v1/logs:ingest
        // 3. Handle response/errors
        // 4. Return response data
        
        throw new \RuntimeException('LogStackClient not implemented yet');
    }

    /**
     * Test connectivity to LogStack service.
     */
    public function ping(): bool
    {
        // TODO: Implement health check
        
        throw new \RuntimeException('LogStackClient ping not implemented yet');
    }
}
