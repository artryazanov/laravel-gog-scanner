<?php

namespace Artryazanov\GogScanner;

use Illuminate\Support\ServiceProvider;
use Artryazanov\GogScanner\Console\GogScanCommand;

class GogScannerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge package config so users can override values in their app config
        $this->mergeConfigFrom(__DIR__ . '/../config/gogscanner.php', 'gogscanner');
    }

    public function boot(): void
    {
        // Publishable configuration
        $this->publishes([
            __DIR__ . '/../config/gogscanner.php' => config_path('gogscanner.php'),
        ], 'config');

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                GogScanCommand::class,
            ]);
        }
    }
}
