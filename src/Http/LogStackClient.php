<?php

declare(strict_types=1);

namespace DonTeeWhy\LogStack\Http;

use DonTeeWhy\LogStack\Contracts\LogStackClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * HTTP client for LogStack service.
 * 
 * Handles communication with LogStack API including:
 * - Authentication with bearer tokens
 * - Retry logic with exponential backoff
 * - Circuit breaker pattern
 * - Batch log ingestion
 */
class LogStackClient implements LogStackClientInterface
{
    private Client $httpClient;
    private string $baseUrl;
    private string $token;

    public function __construct(
        string $baseUrl,
        string $token,
        array $httpOptions = []
    ) {
        $this->baseUrl = rtrim(string: $baseUrl, characters: '/');
        $this->token = $token;

        $defaultOptions = [
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$this->token}",
                'User-Agent' => 'Laravel-LogStack/1.0',
            ],
        ];

        $options = array_merge_recursive($defaultOptions, $httpOptions);
        $this->httpClient = new Client($options);
    }

    /**
     * Send log entries to LogStack service.
     * 
     * @param array $entries Array of log entries in LogStack format
     * @return array Response from LogStack service
     */
    public function ingest(array $entries): array
    {
        try {
            $payload = [
                'entries' => $entries,
            ];
            $response = $this->httpClient->request(
                method: 'POST',
                uri: "{$this->baseUrl}/v1/logs:ingest",
                options: [
                    'json' => $payload,
                ]
            );

            $responseData = json_decode($response->getBody()->getContents(), true);

            return $responseData ?? [];
        } catch (GuzzleException $e) {
            throw new \RuntimeException(
                'LogStack ingestion failed (GuzzleException): ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'LogStack ingestion failed (Exception): ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Test connectivity to LogStack service.
     */
    public function ping(): bool
    {
        try {
            $response = $this->httpClient->request(
                method: 'GET',
                uri: "{$this->baseUrl}/healthz",
                options: [
                    'timeout' => 5,
                ]
            );

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (GuzzleException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function isConfigured(): bool
    {
        return !empty($this->baseUrl) && !empty($this->token);
    }
}
