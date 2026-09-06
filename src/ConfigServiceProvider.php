<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\ServiceProvider;
use JOOservices\LaravelConfig\Console\Commands\DoctorConfigCommand;
use JOOservices\LaravelConfig\Console\Commands\EnsureConfigIndexCommand;
use JOOservices\LaravelConfig\Console\Commands\ExportConfigCommand;
use JOOservices\LaravelConfig\Console\Commands\ForgetConfigCommand;
use JOOservices\LaravelConfig\Console\Commands\GetConfigCommand;
use JOOservices\LaravelConfig\Console\Commands\ImportConfigCommand;
use JOOservices\LaravelConfig\Console\Commands\ListConfigCommand;
use JOOservices\LaravelConfig\Console\Commands\RefreshConfigCommand;
use JOOservices\LaravelConfig\Console\Commands\SetConfigCommand;
use JOOservices\LaravelConfig\Contracts\ConfigStore;
use JOOservices\LaravelConfig\Services\ConfigService;

class ConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/config-store.php',
            'config-store',
        );

        $this->app->singleton(ConfigService::class, function (Application $app): ConfigService {
            $cacheFactory = $app->make('cache');
            $configuredStore = config('config-store.cache_store');
            $defaultStore = config('cache.default', 'array');
            $storeName = is_string($configuredStore) && $configuredStore !== ''
                ? $configuredStore
                : (is_string($defaultStore) ? $defaultStore : 'array');

            /** @var \Illuminate\Contracts\Cache\Repository $cacheRepository */
            $cacheRepository = $cacheFactory->store($storeName);

            return new ConfigService(
                $cacheRepository,
                $app->make(Encrypter::class),
            );
        });

        $this->app->alias(ConfigService::class, 'config-store');
        $this->app->bind(ConfigStore::class, ConfigService::class);
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
                ListConfigCommand::class,
                DoctorConfigCommand::class,
                ExportConfigCommand::class,
                ImportConfigCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/config-store.php' => config_path('config-store.php'),
            ], 'config-store-config');
        }
    }
}
