<?php
declare(strict_types=1);

namespace DonTeeWhy\LogStack\Providers;

use Illuminate\Support\ServiceProvider;
use DonTeeWhy\LogStack\Console\Commands\LogStackMakeCommand;

final class LogStackServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
    }
}