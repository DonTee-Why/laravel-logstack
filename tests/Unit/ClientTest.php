<?php

declare(strict_types=1);

namespace DonTeeWhy\LogStack\Tests\Unit;

use DonTeeWhy\LogStack\Contracts\LogStackClientInterface;
use DonTeeWhy\LogStack\Http\LogStackClient;
use DonTeeWhy\LogStack\Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use RuntimeException;

class ClientTest extends TestCase
{
    public function test_implements_interface(): void
    {
        $client = new LogStackClient('https://example.com', 'token123');
        
        $this->assertInstanceOf(LogStackClientInterface::class, $client);
    }

    public function test_get_base_url(): void
    {
        $client = new LogStackClient('https://example.com/', 'token123');
        
        $this->assertEquals('https://example.com', $client->getBaseUrl());
    }

    public function test_is_configured_returns_true_when_valid(): void
    {
        $client = new LogStackClient('https://example.com', 'token123');
        
        $this->assertTrue($client->isConfigured());
    }

    public function test_is_configured_returns_false_when_empty_url(): void
    {
        $client = new LogStackClient('', 'token123');
        
        $this->assertFalse($client->isConfigured());
    }

    public function test_is_configured_returns_false_when_empty_token(): void
    {
        $client = new LogStackClient('https://example.com', '');
        
        $this->assertFalse($client->isConfigured());
    }

    public function test_ping_returns_true_on_200_response(): void
    {
        $client = $this->createClientWithMockResponse(new Response(200));
        
        $this->assertTrue($client->ping());
    }

    public function test_ping_returns_false_on_error_response(): void
    {
        $client = $this->createClientWithMockResponse(new Response(500));
        
        $this->assertFalse($client->ping());
    }

    public function test_ping_returns_false_on_network_error(): void
    {
        $mock = new MockHandler([
            new ConnectException(
                'Connection error',
                new Request('GET', 'test')
            )
        ]);
        
        $client = $this->createClientWithMockHandler($mock);
        
        $this->assertFalse($client->ping());
    }

    public function test_ingest_success(): void
    {
        $expectedResponse = [
            'message' => 'Logs accepted for processing',
            'entries_accepted' => 2,
            'request_id' => 'req-123',
            'timestamp' => '2024-01-15T10:30:00Z'
        ];
        
        $mockResponse = new Response(
            202,
            ['Content-Type' => 'application/json'],
            json_encode($expectedResponse)
        );
        
        $client = $this->createClientWithMockResponse($mockResponse);
        
        $entries = [
            [
                'timestamp' => '2024-01-15T10:30:00.000Z',
                'level' => 'INFO',
                'message' => 'Test message 1',
                'service' => 'test-service',
                'env' => 'testing',
            ],
            [
                'timestamp' => '2024-01-15T10:30:01.000Z',
                'level' => 'ERROR',
                'message' => 'Test message 2',
                'service' => 'test-service',
                'env' => 'testing',
            ]
        ];

        $result = $client->ingest($entries);
        
        $this->assertEquals($expectedResponse, $result);
    }

    public function test_ingest_throws_exception_on_http_error(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LogStack ingestion failed (GuzzleException)');
        
        $mock = new MockHandler([
            new RequestException(
                'Server error',
                new Request('POST', 'test'),
                new Response(500)
            )
        ]);
        
        $client = $this->createClientWithMockHandler($mock);
        
        $client->ingest([['test' => 'data']]);
    }

    public function test_ingest_throws_exception_on_network_error(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LogStack ingestion failed (GuzzleException)');
        
        $mock = new MockHandler([
            new ConnectException(
                'Connection timeout',
                new Request('POST', 'test')
            )
        ]);
        
        $client = $this->createClientWithMockHandler($mock);
        
        $client->ingest([['test' => 'data']]);
    }

    public function test_ingest_handles_empty_response(): void
    {
        $mockResponse = new Response(202, [], '');
        $client = $this->createClientWithMockResponse($mockResponse);
        
        $result = $client->ingest([['test' => 'data']]);
        
        $this->assertEquals([], $result);
    }

    public function test_ingest_handles_invalid_json_response(): void
    {
        $mockResponse = new Response(202, [], 'invalid json');
        $client = $this->createClientWithMockResponse($mockResponse);
        
        $result = $client->ingest([['test' => 'data']]);
        
        $this->assertEquals([], $result);
    }

    public function test_client_uses_correct_headers(): void
    {
        $mock = new MockHandler([new Response(202, [], '{}')]);
        $handlerStack = HandlerStack::create($mock);
        
        // Add middleware to capture the request
        $capturedRequest = null;
        $handlerStack->push(function (callable $handler) use (&$capturedRequest) {
            return function ($request, array $options) use ($handler, &$capturedRequest) {
                $capturedRequest = $request;
                return $handler($request, $options);
            };
        });
        
        $client = new LogStackClient(
            'https://example.com',
            'test-token-123',
            ['handler' => $handlerStack]
        );
        
        $client->ingest([['test' => 'data']]);
        
        $this->assertNotNull($capturedRequest);
        $this->assertEquals('application/json', $capturedRequest->getHeaderLine('Content-Type'));
        $this->assertEquals('Bearer test-token-123', $capturedRequest->getHeaderLine('Authorization'));
        $this->assertEquals('Laravel-LogStack/1.0', $capturedRequest->getHeaderLine('User-Agent'));
    }

    public function test_client_makes_request_to_correct_endpoint(): void
    {
        $mock = new MockHandler([new Response(202, [], '{}')]);
        $handlerStack = HandlerStack::create($mock);
        
        $capturedRequest = null;
        $handlerStack->push(function (callable $handler) use (&$capturedRequest) {
            return function ($request, array $options) use ($handler, &$capturedRequest) {
                $capturedRequest = $request;
                return $handler($request, $options);
            };
        });
        
        $client = new LogStackClient(
            'https://logstack.example.com',
            'token123',
            ['handler' => $handlerStack]
        );
        
        $client->ingest([['test' => 'data']]);
        
        $this->assertEquals('POST', $capturedRequest->getMethod());
        $this->assertEquals(
            'https://logstack.example.com/v1/logs:ingest',
            (string) $capturedRequest->getUri()
        );
    }

    public function test_ping_makes_request_to_healthz_endpoint(): void
    {
        $mock = new MockHandler([new Response(200)]);
        $handlerStack = HandlerStack::create($mock);
        
        $capturedRequest = null;
        $handlerStack->push(function (callable $handler) use (&$capturedRequest) {
            return function ($request, array $options) use ($handler, &$capturedRequest) {
                $capturedRequest = $request;
                return $handler($request, $options);
            };
        });
        
        $client = new LogStackClient(
            'https://logstack.example.com',
            'token123',
            ['handler' => $handlerStack]
        );
        
        $client->ping();
        
        $this->assertEquals('GET', $capturedRequest->getMethod());
        $this->assertEquals(
            'https://logstack.example.com/healthz',
            (string) $capturedRequest->getUri()
        );
    }

    private function createClientWithMockResponse(Response $response): LogStackClient
    {
        $mock = new MockHandler([$response]);
        return $this->createClientWithMockHandler($mock);
    }

    private function createClientWithMockHandler(MockHandler $mock): LogStackClient
    {
        $handlerStack = HandlerStack::create($mock);
        
        return new LogStackClient(
            'https://example.com',
            'token123',
            ['handler' => $handlerStack]
        );
    }
}
