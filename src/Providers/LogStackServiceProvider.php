<?php

declare(strict_types=1);

namespace DonTeeWhy\LogStack\Providers;

use DonTeeWhy\LogStack\Drivers\LogStackDriver;
use Illuminate\Support\ServiceProvider;

class LogStackServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->extend('log', function ($log) {
            $log->extend('logstack', function ($app, $config) {
                return (new LogStackDriver())($config);
            });
            return $log;
        });

        $this->publishes([
            __DIR__ . '/../../config/logstack.php' => config_path('logstack.php'),
        ], 'logstack-config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/logstack.php', 'logstack');
    }
}
