<?php
declare(strict_types=1);

namespace DonTeeWhy\LogStack\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * LogStack facade for convenient access to logging functionality.
 * 
 * Provides static access to LogStack operations:
 * 
 * @method static void emergency(string $message, array $context = [])
 * @method static void alert(string $message, array $context = [])
 * @method static void critical(string $message, array $context = [])
 * @method static void error(string $message, array $context = [])
 * @method static void warning(string $message, array $context = [])
 * @method static void notice(string $message, array $context = [])
 * @method static void info(string $message, array $context = [])
 * @method static void debug(string $message, array $context = [])
 * @method static void log(string $level, string $message, array $context = [])
 * @method static bool ping()
 * @method static array getConfig()
 * 
 * @example LogStack::info('User logged in', ['user_id' => 123])
 * @example LogStack::error('Payment failed', ['order_id' => $order->id, 'error' => $e->getMessage()])
 * @example LogStack::ping() // Test connectivity
 */
final class LogStack extends Facade
{
    /**
     * Get the registered name of the component.
     * 
     * This should match the binding key in the service provider.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'logstack';
    }
}
