<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Tests;

use Illuminate\Support\Facades\Event;
use JOOservices\LaravelConfig\Events\ConfigChanged;
use JOOservices\LaravelConfig\Events\ConfigForgotten;
use JOOservices\LaravelConfig\Facades\Config;

class ConfigEventsTest extends TestCase
{
    public function test_set_dispatches_config_changed_event(): void
    {
        Event::fake([ConfigChanged::class]);

        Config::set('system.site_name', 'XCrawler');

        Event::assertDispatched(ConfigChanged::class, function (ConfigChanged $event): bool {
            return $event->path === 'system.site_name'
                && $event->value === 'XCrawler'
                && $event->type === 'string'
                && $event->isRedacted() === false;
        });
    }

    public function test_set_encrypted_redacts_config_changed_event(): void
    {
        Event::fake([ConfigChanged::class]);

        Config::set('secrets.api_token', 'super-secret', 'encrypted');

        Event::assertDispatched(ConfigChanged::class, function (ConfigChanged $event): bool {
            return $event->path === 'secrets.api_token'
                && $event->value === null
                && $event->type === 'encrypted'
                && $event->isRedacted() === true;
        });
    }

    public function test_forget_dispatches_config_forgotten_event(): void
    {
        Config::set('system.temp', 'value');

        Event::fake([ConfigForgotten::class]);

        Config::forget('system.temp');

        Event::assertDispatched(ConfigForgotten::class, function (ConfigForgotten $event): bool {
            return $event->path === 'system.temp';
        });
    }
}
