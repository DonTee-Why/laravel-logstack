<?php
declare(strict_types=1);

namespace DonTeeWhy\LogStack\Drivers;

use DonTeeWhy\LogStack\Config\LogStackConfig;
use DonTeeWhy\LogStack\Formatters\LogStackFormatter;
use DonTeeWhy\LogStack\Handlers\LogStackHandler;
use DonTeeWhy\LogStack\Http\LogStackClient;
use Monolog\Logger;

/**
 * LogStack custom logging driver for Laravel.
 * 
 * Creates and configures a Monolog logger instance with LogStack handler.
 */
final class LogStackDriver
{
    /**
     * Create a custom LogStack logger instance.
     */
    public function __invoke(array $config): Logger
    {
        $logStackConfig = new LogStackConfig(config: $config, laravelConfig: config('logstack', []));
        $client = new LogStackClient(
            baseUrl: $logStackConfig->getUrl(),
            token: $logStackConfig->getToken(),
        );
        $formatter = new LogStackFormatter(
            serviceName: $logStackConfig->getServiceName(),
            environment: $logStackConfig->getEnvironment(),
            defaultLabels: $logStackConfig->getDefaultLabels(),
        );
        $handler = new LogStackHandler(
            client: $client,
            formatter: $formatter,
            async: $logStackConfig->isAsync(),
            batchSize: $logStackConfig->getBatchSize(),
            batchTimeoutMs: $logStackConfig->getTimeout(),
            queueConnection: $logStackConfig->getQueueConnection(),
        );
        $logger = new Logger(name: 'logstack');
        $logger->pushHandler(handler: $handler);

        return $logger;
    }
}
