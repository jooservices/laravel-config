<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Tests;

use Illuminate\Contracts\Console\Kernel;
use JOOservices\LaravelConfig\ConfigServiceProvider;
use JOOservices\LaravelConfig\Contracts\ConfigStore;
use JOOservices\LaravelConfig\Services\ConfigService;

class ConfigServiceProviderTest extends TestCase
{
    public function test_container_resolves_config_service_with_explicit_cache_store(): void
    {
        $app = $this->app;
        $this->assertNotNull($app);

        $app['config']->set('config-store.cache_store', 'array');
        $app->register(ConfigServiceProvider::class);

        $service = $app->make(ConfigService::class);

        $this->assertInstanceOf(ConfigService::class, $service);
        $this->assertSame($service, $app->make(ConfigStore::class));
    }

    public function test_container_resolves_config_service_when_cache_default_is_not_string(): void
    {
        $this->app['config']->set('config-store.cache_store', null);
        $this->app['config']->set('cache.default', null);
        $this->app->forgetInstance(ConfigService::class);

        $service = $this->app->make(ConfigService::class);

        $this->assertInstanceOf(ConfigService::class, $service);
    }

    public function test_provider_registers_console_commands(): void
    {
        /** @var Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);
        $commands = $kernel->all();

        $this->assertArrayHasKey('config-store:get', $commands);
        $this->assertArrayHasKey('config-store:doctor', $commands);
        $this->assertArrayHasKey('config-store:import', $commands);
    }
}
