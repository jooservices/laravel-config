<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Tests;

use JOOservices\LaravelConfig\ConfigServiceProvider;
use JOOservices\LaravelConfig\Contracts\ConfigStore;
use JOOservices\LaravelConfig\Facades\Config;
use JOOservices\LaravelConfig\Models\Config as ConfigModel;
use JOOservices\LaravelConfig\Services\ConfigService;
use MongoDB\Laravel\MongoDBServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearConfigStore();
    }

    protected function getPackageProviders($app): array
    {
        return [
            MongoDBServiceProvider::class,
            ConfigServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Config' => Config::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.connections.mongodb', [
            'driver' => 'mongodb',
            'dsn' => env('MONGODB_URI', env('MONGO_URI', 'mongodb://localhost:27017')),
            'database' => env('MONGODB_DATABASE', 'jooservices_configs_test'),
        ]);

        $app['config']->set('config-store.cache_enabled', true);
        $app['config']->set('config-store.cache_key', 'jooservices_config_all_test');
        $app['config']->set('config-store.cache_store', 'array');
    }

    protected function clearConfigStore(): void
    {
        ConfigModel::query()->delete();
        $this->resetConfigStoreService();
        $this->app->make(ConfigStore::class)->refresh();
    }

    protected function resetConfigStoreService(): void
    {
        $this->app->forgetInstance(ConfigService::class);
        $this->app->forgetInstance('config-store');
        $this->app->forgetInstance(ConfigStore::class);
        Config::clearResolvedInstance(ConfigStore::class);
    }

    protected function makeConfigService(): ConfigService
    {
        return $this->app->make(ConfigService::class);
    }
}
