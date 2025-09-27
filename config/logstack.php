<?php

declare(strict_types=1);

/**
 * LogStack package configuration.
 * 
 * This file will be published to the Laravel application's config directory.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | LogStack Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the connection to your LogStack service instance.
    |
    */

    'url' => env('LOGSTACK_URL'),
    'token' => env('LOGSTACK_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Service Identification
    |--------------------------------------------------------------------------
    |
    | These values identify your Laravel application in LogStack.
    |
    */

    'service_name' => env('LOGSTACK_SERVICE', env('APP_NAME', 'laravel-app')),
    'environment' => env('LOGSTACK_ENV', env('APP_ENV', 'production')),

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configure async processing and batching for optimal performance.
    |
    */

    'async' => env('LOGSTACK_ASYNC', true),
    'batch_size' => env('LOGSTACK_BATCH_SIZE', 50),
    'batch_timeout_ms' => env('LOGSTACK_BATCH_TIMEOUT', 5000),
    'queue_connection' => env('LOGSTACK_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Default Labels
    |--------------------------------------------------------------------------
    |
    | Labels automatically added to all log entries.
    |
    */

    'default_labels' => [
        'region' => env('AWS_REGION'),
        'version' => env('APP_VERSION'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    |
    | Configure timeouts and retry behavior.
    |
    */

    'timeout' => 30,
    'retry_attempts' => 3,
    'retry_delay_ms' => [5000, 10000, 20000],

    /*
    |--------------------------------------------------------------------------
    | Label Extractors
    |--------------------------------------------------------------------------
    |
    | Classes that extract dynamic labels from the current request/context.
    |
    */

    'label_extractors' => [
        // 'tenant' => \App\LogStack\Extractors\TenantLabelExtractor::class,
        // 'user_type' => \App\LogStack\Extractors\UserTypeLabelExtractor::class,
    ],
];
