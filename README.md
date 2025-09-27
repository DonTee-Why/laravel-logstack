# Laravel LogStack

<!-- [![Latest Version on Packagist](https://img.shields.io/packagist/v/dontee-why/laravel-logstack.svg?style=flat-square)](https://packagist.org/packages/dontee-why/laravel-logstack)
[![Total Downloads](https://img.shields.io/packagist/dt/dontee-why/laravel-logstack.svg?style=flat-square)](https://packagist.org/packages/dontee-why/laravel-logstack) -->

A Laravel package that provides seamless integration with LogStack log ingestion service. Enables centralized logging across multiple Laravel applications with native Laravel logging interface compatibility.

## Features

- 🚀 **Native Laravel Integration** - Works with existing `Log::` calls
- 📦 **Async Batching** - Efficient log batching with queue support
- 🔒 **Secure** - Bearer token authentication with per-app API keys
- 🏷️ **Smart Labels** - Automatic extraction of contextual labels
- 🔄 **Reliable** - Error handling with fallback mechanisms
- ⚡ **Performant** - <5ms overhead in async mode

## Requirements

- PHP 8.1+
- Laravel 9.0+
- LogStack service instance

## Installation

Install the package via Composer:

```bash
composer require dontee-why/laravel-logstack
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=logstack-config
# OR
php artisan vendor:publish --provider="DonTeeWhy\LogStack\Providers\LogStackServiceProvider" --tag="logstack-config"
```

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
LOGSTACK_URL=https://your-logstack-service.com
LOGSTACK_TOKEN=your-api-token
LOGSTACK_SERVICE=your-app-name
LOGSTACK_ENV=production
LOGSTACK_ASYNC=true
LOGSTACK_BATCH_SIZE=50
```

### Laravel Logging Channel

Add the LogStack channel to your `config/logging.php`:

```php
'channels' => [
    'logstack' => [
        'driver' => 'logstack',
        'level' => 'info',
    ],
    
    // Optional: Use with stack for dual logging
    'stack' => [
        'driver' => 'stack',
        'channels' => ['logstack', 'daily'],
    ],
],
```

### Configuration Options

The package publishes a `config/logstack.php` file with the following options:

```php
return [
    // Service Configuration
    'url' => env('LOGSTACK_URL'),
    'token' => env('LOGSTACK_TOKEN'),
    'service_name' => env('LOGSTACK_SERVICE', env('APP_NAME')),
    'environment' => env('LOGSTACK_ENV', env('APP_ENV')),
    
    // Performance
    'async' => env('LOGSTACK_ASYNC', true),
    'batch_size' => env('LOGSTACK_BATCH_SIZE', 50),
    'batch_timeout_ms' => env('LOGSTACK_BATCH_TIMEOUT', 5000),
    'queue_connection' => env('LOGSTACK_QUEUE', 'default'),
    
    // Default Labels
    'default_labels' => [
        'region' => env('AWS_REGION'),
        'version' => env('APP_VERSION'),
    ],
    
    // HTTP Client
    'timeout' => 30,
    'retry_attempts' => 3,
    'retry_delay_ms' => [5000, 10000, 20000],
];
```

## Usage

### Basic Logging

Use Laravel's native logging methods - no changes to your existing code:

```php
use Illuminate\Support\Facades\Log;

// Basic logging
Log::channel('logstack')->info('User logged in');
Log::channel('logstack')->error('Payment failed');

// With context
Log::channel('logstack')->info('Order processed', [
    'order_id' => $order->id,
    'user_id' => auth()->id(),
    'amount' => $order->total,
]);

// With labels (will be extracted automatically)
Log::channel('logstack')->warning('High memory usage', [
    'memory_mb' => 512,
    'region' => 'us-east-1',    // Becomes a LogStack label
    'tenant' => 'company-xyz',  // Becomes a LogStack label
]);
```

### Log Levels

All Laravel log levels are supported:

```php
Log::channel('logstack')->debug('Debug information');
Log::channel('logstack')->info('General information');
Log::channel('logstack')->notice('Normal but significant condition');
Log::channel('logstack')->warning('Warning condition');
Log::channel('logstack')->error('Error condition');
Log::channel('logstack')->critical('Critical condition');
Log::channel('logstack')->alert('Action must be taken immediately');
Log::channel('logstack')->emergency('System is unusable');
```

### Advanced Usage

#### Custom Labels

Certain context keys are automatically converted to LogStack labels:

```php
Log::channel('logstack')->info('API request', [
    'endpoint' => '/api/users',     // Goes to metadata
    'status' => 200,               // Goes to metadata
    'region' => 'us-west-2',       // Becomes a label
    'tenant' => 'customer-123',    // Becomes a label
    'schema_version' => 'v2',      // Becomes a label
]);
```

#### Sync vs Async Logging

```php
// Force sync logging (immediate HTTP request)
config(['logstack.async' => false]);
Log::channel('logstack')->critical('Critical error');

// Async logging (queued - default)
config(['logstack.async' => true]);
Log::channel('logstack')->info('Background process completed');
```

## LogStack Integration

This package integrates with LogStack service, which provides:

- **Write-Ahead Log (WAL)** for durability
- **Data masking** for sensitive information
- **Batch processing** for performance
- **Async forwarding** to Grafana Loki

### Log Format

Logs are automatically transformed to LogStack format:

```json
{
    "timestamp": "2024-01-15T10:30:00.000Z",
    "level": "ERROR",
    "message": "Payment processing failed",
    "service": "laravel-ecommerce",
    "env": "production",
    "labels": {
        "region": "us-east-1",
        "tenant": "customer-123"
    },
    "metadata": {
        "order_id": 12345,
        "user_id": 67890,
        "error_code": "PAYMENT_DECLINED"
    }
}
```

## Queue Configuration

For async logging, ensure your Laravel queue is configured and running:

```bash
# Start queue worker
php artisan queue:work

# Or use Supervisor for production
# See: https://laravel.com/docs/queues#supervisor-configuration
```

The package uses your default queue connection but can be customized:

```php
// In config/logstack.php
'queue_connection' => 'redis', // Use specific connection
```

## Error Handling

The package includes robust error handling:

- **Network failures**: Logs errors but doesn't break your application
- **LogStack downtime**: Graceful degradation with local error logging
- **Queue failures**: Automatic fallback to sync mode
- **Configuration errors**: Clear error messages with suggestions

## Performance

- **Async mode**: <5ms overhead per log entry
- **Sync mode**: <100ms overhead per log entry
- **Memory usage**: <10MB additional under normal load
- **Throughput**: 1000+ logs/minute per Laravel instance

## Testing

You can test the integration by checking if logs reach your LogStack service:

```php
// Test logging
Log::channel('logstack')->info('Test message from Laravel', [
    'test' => true,
    'timestamp' => now(),
]);

// Check LogStack service health
$client = app(\DonTeeWhy\LogStack\Http\LogStackClient::class);
$isHealthy = $client->ping(); // Returns true/false
```

## Troubleshooting

### Common Issues

1. LogStack URL not configured

   - Ensure `LOGSTACK_URL` is set in your `.env` file
   - Verify the URL is accessible from your Laravel application

2. LogStack token not configured

   - Set `LOGSTACK_TOKEN` in your `.env` file
   - Verify the token is valid with your LogStack service

3. Logs not appearing

   - Check your queue is running (`php artisan queue:work`)
   - Verify LogStack service is reachable
   - Check Laravel logs for error messages

4. High memory usage

   - Reduce `batch_size` in config
   - Ensure queue workers are processing jobs

<!-- ### Debug Mode

Enable debug logging to troubleshoot issues:

```php
// Temporarily in AppServiceProvider boot()
\Illuminate\Support\Facades\Log::debug('LogStack config', config('logstack'));
``` -->

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Security

If you discover any security-related issues, please email [timiddon97@gmail.com](mailto:timiddon97@gmail.com) instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

- [Documentation](https://github.com/DonTee-Why/laravel-logstack/wiki)
- [Issue Tracker](https://github.com/DonTee-Why/laravel-logstack/issues)
- [Discussions](https://github.com/DonTee-Why/laravel-logstack/discussions)
