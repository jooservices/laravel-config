<?php

declare(strict_types=1);

namespace JooServices\LaravelConfig\Tests;

use JooServices\LaravelConfig\ConfigServiceProvider;
use JooServices\LaravelConfig\Facades\Config;
use JooServices\LaravelConfig\Models\Config as ConfigModel;
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
        $this->app->make('config-store')->refresh();
    }
}
