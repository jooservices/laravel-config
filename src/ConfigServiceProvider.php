<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig;

use Illuminate\Support\ServiceProvider;
use JOOservices\LaravelConfig\Console\Commands\EnsureConfigIndexCommand;
use JOOservices\LaravelConfig\Console\Commands\ForgetConfigCommand;
use JOOservices\LaravelConfig\Console\Commands\GetConfigCommand;
use JOOservices\LaravelConfig\Console\Commands\RefreshConfigCommand;
use JOOservices\LaravelConfig\Console\Commands\SetConfigCommand;
use JOOservices\LaravelConfig\Services\ConfigService;

class ConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/config-store.php',
            'config-store'
        );

        $this->app->singleton('config-store', ConfigService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GetConfigCommand::class,
                SetConfigCommand::class,
                ForgetConfigCommand::class,
                RefreshConfigCommand::class,
                EnsureConfigIndexCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/config-store.php' => config_path('config-store.php'),
            ], 'config-store-config');
        }
    }
}
