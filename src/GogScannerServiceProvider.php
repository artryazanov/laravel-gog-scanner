<?php

namespace Artryazanov\GogScanner;

use Artryazanov\GogScanner\Console\GogScanCommand;
use Illuminate\Support\ServiceProvider;

class GogScannerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge package config so users can override values in their app config
        $this->mergeConfigFrom(__DIR__.'/../config/gogscanner.php', 'gogscanner');
    }

    public function boot(): void
    {
        // Publishable configuration
        $this->publishes([
            __DIR__.'/../config/gogscanner.php' => config_path('gogscanner.php'),
        ], 'config');

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                GogScanCommand::class,
            ]);
        }
    }
}
