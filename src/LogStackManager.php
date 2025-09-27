<?php
declare(strict_types=1);

namespace DonTeeWhy\LogStack;

use DonTeeWhy\LogStack\Contracts\LogStackClientInterface;
use DonTeeWhy\LogStack\Contracts\LogStackConfigInterface;
use Psr\Log\LoggerInterface;

/**
 * LogStack manager class.
 * 
 * Main service class that provides the public API for the LogStack facade.
 * Handles direct logging operations and service management.
 */
final class LogStackManager implements LoggerInterface
{
    private LogStackClientInterface $client;
    private LogStackConfigInterface $config;
    private LoggerInterface $logger;

    public function __construct(
        LogStackClientInterface $client,
        LogStackConfigInterface $config,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Test connectivity to LogStack service.
     */
    public function ping(): bool
    {
        return $this->client->ping();
    }

    /**
     * Get current configuration.
     */
    public function getConfig(): array
    {
        return [
            'url' => $this->config->getUrl(),
            'service_name' => $this->config->getServiceName(),
            'environment' => $this->config->getEnvironment(),
            'async' => $this->config->isAsync(),
            'batch_size' => $this->config->getBatchSize(),
        ];
    }

    /**
     * System is unusable.
     */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    /**
     * Action must be taken immediately.
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    /**
     * Critical conditions.
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action.
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * Normal but significant events.
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    /**
     * Interesting events.
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * Detailed debug information.
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    /**
     * Logs with an arbitrary level.
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
