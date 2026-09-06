<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Tests;

use InvalidArgumentException;
use JOOservices\LaravelConfig\Facades\Config;
use JOOservices\LaravelConfig\Testing\FakeConfigStore;

class FakeConfigStoreTest extends TestCase
{
    public function test_fake_config_store_seeds_values(): void
    {
        Config::fake([
            'system' => [
                'site_name' => 'XCrawler',
            ],
        ]);

        $this->assertSame('XCrawler', Config::get('system.site_name'));
    }

    public function test_fake_config_store_supports_mutations(): void
    {
        Config::fake();
        Config::set('payment.retry_times', 3);

        $this->assertSame(3, Config::getInt('payment.retry_times'));
    }

    public function test_fake_config_store_forget_group_and_all(): void
    {
        $fake = new FakeConfigStore([
            'system' => ['site_name' => 'XCrawler', 'temp' => 'x'],
        ]);

        $this->assertSame(['site_name' => 'XCrawler', 'temp' => 'x'], $fake->group('system'));
        $this->assertArrayHasKey('system', $fake->all());
        $this->assertTrue($fake->forget('system.temp'));
        $this->assertFalse($fake->forget('system.missing'));
        $this->assertFalse($fake->has('system.temp'));
    }

    public function test_fake_config_store_list_ordered_returns_sorted_models(): void
    {
        $fake = new FakeConfigStore([
            'payment' => ['b' => 2, 'a' => 3],
            'system' => ['a' => 1],
        ]);

        $items = $fake->listOrdered();

        $paths = $items->map(
            fn(array $row): string => $row['group'] . '.' . $row['key'],
        )->all();

        $this->assertSame(['payment.a', 'payment.b', 'system.a'], $paths);
    }

    public function test_fake_config_store_remember_and_fresh(): void
    {
        $fake = new FakeConfigStore();

        $this->assertSame('default', $fake->remember('system.remembered', 'default'));
        $this->assertSame('default', $fake->fresh('system.remembered'));
        $this->assertSame('default', $fake->remember('system.remembered', 'other'));
    }

    public function test_fake_config_store_typed_getters_return_matching_types(): void
    {
        $fake = new FakeConfigStore([
            'typed' => [
                'name' => 'XCrawler',
                'count' => 3,
                'rate' => 1.5,
                'enabled' => true,
                'items' => ['a' => 1, 0 => 'numeric-key'],
            ],
        ]);

        $this->assertSame('XCrawler', $fake->getString('typed.name'));
        $this->assertSame('fallback', $fake->getString('typed.missing', 'fallback'));
        $this->assertSame(3, $fake->getInt('typed.count'));
        $this->assertSame(1.5, $fake->getFloat('typed.rate'));
        $this->assertTrue($fake->getBool('typed.enabled'));
        $this->assertSame(['a' => 1], $fake->getArray('typed.items'));
        $this->assertSame(['fallback' => true], $fake->getArray('typed.missing', ['fallback' => true]));
    }

    public function test_fake_config_store_normalizes_set_values_by_type(): void
    {
        $fake = new FakeConfigStore();

        $fake->set('typed.name', 'XCrawler');
        $fake->set('typed.count', '3', 'int');
        $fake->set('typed.rate', '1.5', 'float');
        $fake->set('typed.enabled', 'true', 'bool');
        $fake->set('typed.items', ['a' => 1], 'array');
        $fake->set('typed.json', ['a' => 1], 'json');
        $fake->set('typed.null', null, 'null');

        $this->assertSame('XCrawler', $fake->getString('typed.name'));
        $this->assertSame(3, $fake->getInt('typed.count'));
        $this->assertSame(1.5, $fake->getFloat('typed.rate'));
        $this->assertTrue($fake->getBool('typed.enabled'));
        $this->assertSame(['a' => 1], $fake->getArray('typed.items'));
        $this->assertSame(['a' => 1], $fake->getArray('typed.json'));
        $this->assertNull($fake->get('typed.null'));
    }

    public function test_fake_config_store_rejects_invalid_type(): void
    {
        $fake = new FakeConfigStore();

        $this->expectException(InvalidArgumentException::class);
        $fake->set('system.bad', 'value', 'boolean');
    }

    public function test_fake_config_store_get_array_returns_default_for_non_array_values(): void
    {
        $fake = new FakeConfigStore(['typed' => ['text' => 'hello']]);

        $this->assertSame(['fallback' => true], $fake->getArray('typed.text', ['fallback' => true]));
    }

    public function test_fake_config_store_forget_removes_empty_group_bucket(): void
    {
        $fake = new FakeConfigStore(['system' => ['only' => 'value']]);

        $this->assertTrue($fake->forget('system.only'));
        $this->assertSame([], $fake->all());
    }

    public function test_fake_config_store_refresh_and_indexes_are_no_ops(): void
    {
        $fake = new FakeConfigStore(['system' => ['site_name' => 'XCrawler']]);

        $fake->refresh();

        $this->assertSame('XCrawler', $fake->get('system.site_name'));
        $this->assertSame('config_group_key_unique', $fake->ensureIndexes());
    }

    public function test_fake_config_store_skips_invalid_seed_entries(): void
    {
        $fake = new FakeConfigStore([
            123 => ['ignored' => 'x'],
            'system' => [
                0 => 'numeric-key',
                'ok' => 'value',
            ],
        ]);

        $this->assertSame(['ok' => 'value'], $fake->group('system'));
    }

    public function test_fake_config_store_set_many_rejects_empty_path(): void
    {
        $fake = new FakeConfigStore();

        $this->expectException(InvalidArgumentException::class);
        $fake->setMany(['' => 'value']);
    }

    public function test_fake_config_store_encrypted_normalizes_to_string(): void
    {
        $fake = new FakeConfigStore();
        $fake->set('  secrets.token  ', 'plain', 'encrypted');

        $this->assertSame('plain', $fake->get('secrets.token'));
        $this->assertSame('encrypted', $fake->listOrdered()->first()['type'] ?? null);
    }
}
