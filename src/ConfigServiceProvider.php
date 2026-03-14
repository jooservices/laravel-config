<?php

declare(strict_types=1);

namespace JooServices\LaravelConfig;

use Illuminate\Support\ServiceProvider;
use JooServices\LaravelConfig\Services\ConfigService;

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
            $this->publishes([
                __DIR__.'/../config/config-store.php' => config_path('config-store.php'),
            ], 'config-store-config');
        }
    }
}
