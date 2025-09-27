<?php

declare(strict_types=1);

namespace DonTeeWhy\LogStack\Providers;

use DonTeeWhy\LogStack\Drivers\LogStackDriver;
use Illuminate\Support\ServiceProvider;
use DonTeeWhy\LogStack\Console\Commands\LogStackMakeCommand;

final class LogStackServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // 1. Register the custom driver
        $this->app->extend('log', function ($log) {
            $log->extend('logstack', function ($app, $config) {
                return (new LogStackDriver())($config);
            });
            return $log;
        });

        // 2. Publish config
        $this->publishes([
            __DIR__ . '/../../config/logstack.php' => config_path('logstack.php'),
        ], 'logstack-config');
    }

    public function register(): void
    {
        // 1. Merge config
        $this->mergeConfigFrom(__DIR__ . '/../../config/logstack.php', 'logstack');
    }
}
