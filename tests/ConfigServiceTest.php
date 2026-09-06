<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Tests;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use JOOservices\LaravelConfig\Contracts\ConfigStore;
use JOOservices\LaravelConfig\Facades\Config;
use JOOservices\LaravelConfig\Models\Config as ConfigModel;
use JOOservices\LaravelConfig\Services\ConfigService;

class ConfigServiceTest extends TestCase
{
    private const INVALID_PATH_MESSAGE = 'Invalid config path';

    public function test_get_returns_default_when_key_missing(): void
    {
        $this->assertNull(Config::get('system.site_name'));
        $this->assertSame('Default', Config::get('system.site_name', 'Default'));
    }

    public function test_set_and_get_string(): void
    {
        Config::set('system.site_name', 'XCrawler');

        $this->assertSame('XCrawler', Config::get('system.site_name'));
        $this->assertSame('XCrawler', Config::get('system.site_name', 'Default'));
    }

    public function test_set_and_get_int(): void
    {
        Config::set('payment.retry_times', 3);

        $this->assertSame(3, Config::get('payment.retry_times'));
        $this->assertIsInt(Config::get('payment.retry_times'));
    }

    public function test_set_and_get_float(): void
    {
        Config::set('payment.rate', 1.5);

        $this->assertSame(1.5, Config::get('payment.rate'));
        $this->assertIsFloat(Config::get('payment.rate'));
    }

    public function test_set_and_get_bool(): void
    {
        Config::set('system.enabled', true);
        $this->assertTrue(Config::get('system.enabled'));

        Config::set('system.disabled', false);
        $this->assertFalse(Config::get('system.disabled'));
    }

    public function test_set_and_get_array(): void
    {
        $arr = ['a' => 1, 'b' => 2];
        Config::set('system.items', $arr);

        $this->assertSame($arr, Config::get('system.items'));
        $this->assertIsArray(Config::get('system.items'));
    }

    public function test_set_and_get_json_type(): void
    {
        Config::set('system.payload', '{"foo":"bar"}', 'json');

        $this->assertSame(['foo' => 'bar'], Config::get('system.payload'));
        $this->assertIsArray(Config::get('system.payload'));
    }

    public function test_set_json_type_with_array_value_stores_encoded(): void
    {
        Config::set('system.encoded', ['nested' => true], 'json');
        $this->assertSame(['nested' => true], Config::get('system.encoded'));
    }

    public function test_set_and_get_null(): void
    {
        Config::set('system.optional', null, 'null');

        $this->assertNull(Config::get('system.optional'));
    }

    public function test_overwrite_existing_config(): void
    {
        Config::set('system.site_name', 'Old');
        $this->assertSame('Old', Config::get('system.site_name'));

        Config::set('system.site_name', 'New');
        $this->assertSame('New', Config::get('system.site_name'));

        $count = ConfigModel::where('group', 'system')->where('key', 'site_name')->count();
        $this->assertSame(1, $count);
    }

    public function test_has_returns_true_when_key_exists(): void
    {
        Config::set('system.site_name', 'XCrawler');

        $this->assertTrue(Config::has('system.site_name'));
    }

    public function test_has_returns_false_when_key_missing(): void
    {
        $this->assertFalse(Config::has('system.missing'));
    }

    public function test_forget_removes_config(): void
    {
        Config::set('system.temp', 'value');
        $this->assertTrue(Config::has('system.temp'));

        $result = Config::forget('system.temp');
        $this->assertTrue($result);
        $this->assertFalse(Config::has('system.temp'));
        $this->assertNull(Config::get('system.temp'));
    }

    public function test_forget_returns_false_when_key_missing(): void
    {
        $result = Config::forget('system.nonexistent');
        $this->assertFalse($result);
    }

    public function test_list_ordered_returns_sorted_models(): void
    {
        Config::set('payment.b', 2);
        Config::set('system.a', 1);
        Config::set('payment.a', 3);

        $items = Config::listOrdered();

        $paths = $items->map(
            fn(array $row): string => $row['group'] . '.' . $row['key'],
        )->all();

        $this->assertSame(['payment.a', 'payment.b', 'system.a'], $paths);
    }

    public function test_group_returns_group_config(): void
    {
        Config::set('system.a', 1);
        Config::set('system.b', 2);
        Config::set('payment.x', 'y');

        $group = Config::group('system');
        $this->assertSame(['a' => 1, 'b' => 2], $group);
    }

    public function test_group_returns_empty_array_for_missing_group(): void
    {
        $this->assertSame([], Config::group('nonexistent'));
    }

    public function test_all_returns_entire_map(): void
    {
        Config::set('system.site_name', 'XCrawler');
        Config::set('payment.gateway', 'stripe');

        $all = Config::all();
        $this->assertArrayHasKey('system', $all);
        $this->assertArrayHasKey('payment', $all);
        $this->assertSame('XCrawler', $all['system']['site_name']);
        $this->assertSame('stripe', $all['payment']['gateway']);
    }

    public function test_refresh_reloads_from_storage(): void
    {
        Config::set('system.site_name', 'Before');
        $this->assertSame('Before', Config::get('system.site_name'));

        $record = ConfigModel::where('group', 'system')->where('key', 'site_name')->first();
        $this->assertNotNull($record);
        /** @var ConfigModel $record */
        $record->value = 'After';
        $record->save();

        $this->assertSame('Before', Config::get('system.site_name'));

        Config::refresh();
        $this->assertSame('After', Config::get('system.site_name'));
    }

    public function test_fresh_returns_value_from_database_bypassing_cache(): void
    {
        Config::set('system.site_name', 'Cached');
        $this->assertSame('Cached', Config::get('system.site_name'));

        $record = ConfigModel::where('group', 'system')->where('key', 'site_name')->first();
        $this->assertNotNull($record);
        /** @var ConfigModel $record */
        $record->value = 'Fresh';
        $record->save();

        $this->assertSame('Cached', Config::get('system.site_name'));
        $this->assertSame('Fresh', Config::fresh('system.site_name'));
    }

    public function test_fresh_returns_default_when_missing(): void
    {
        $this->assertSame('Default', Config::fresh('system.missing', 'Default'));
        $this->assertNull(Config::fresh('system.missing'));
    }

    public function test_cache_updated_after_set(): void
    {
        Config::set('system.key1', 'v1');
        $this->resetConfigStoreService();

        $this->assertSame('v1', Config::get('system.key1'));
    }

    public function test_cache_updated_after_forget(): void
    {
        Config::set('system.key2', 'v2');
        Config::forget('system.key2');
        $this->assertFalse(Config::has('system.key2'));
    }

    public function test_invalid_path_empty_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::INVALID_PATH_MESSAGE);

        Config::get('');
    }

    public function test_invalid_path_single_segment_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Must be group.key');

        Config::get('system');
    }

    public function test_invalid_path_leading_dot_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::INVALID_PATH_MESSAGE);

        Config::get('.system.key');
    }

    public function test_invalid_path_trailing_dot_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::INVALID_PATH_MESSAGE);

        Config::get('system.');
    }

    public function test_invalid_path_double_dot_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Must be group.key');

        Config::get('system..site_name');
    }

    public function test_set_invalid_path_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Config::set('system', 'value');
    }

    public function test_has_invalid_path_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Config::has('system');
    }

    public function test_forget_invalid_path_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Config::forget('single');
    }

    public function test_fresh_invalid_path_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Config::fresh('system');
    }

    public function test_facade_resolves_service(): void
    {
        $this->assertInstanceOf(ConfigService::class, Config::getFacadeRoot());
    }

    public function test_type_normalization_int_from_string(): void
    {
        ConfigModel::create([
            'group' => 'norm',
            'key' => 'num',
            'value' => '42',
            'type' => 'int',
        ]);
        Config::refresh();

        $this->assertSame(42, Config::get('norm.num'));
        $this->assertIsInt(Config::get('norm.num'));
    }

    public function test_type_normalization_bool_from_string(): void
    {
        ConfigModel::create([
            'group' => 'norm',
            'key' => 'flag',
            'value' => 'true',
            'type' => 'bool',
        ]);
        Config::refresh();

        $this->assertTrue(Config::get('norm.flag'));
    }

    public function test_type_normalization_float_from_string(): void
    {
        ConfigModel::create([
            'group' => 'norm',
            'key' => 'rate',
            'value' => '3.14',
            'type' => 'float',
        ]);
        Config::refresh();

        $this->assertSame(3.14, Config::get('norm.rate'));
        $this->assertIsFloat(Config::get('norm.rate'));
    }

    public function test_default_value_used_when_key_missing(): void
    {
        $this->assertSame(99, Config::get('x.y', 99));
        $this->assertSame([], Config::get('x.y', []));
    }

    public function test_service_is_singleton(): void
    {
        $a = $this->app->make('config-store');
        $b = $this->app->make('config-store');
        $this->assertSame($a, $b);
    }

    public function test_set_with_explicit_type(): void
    {
        Config::set('system.count', '42', 'int');
        $this->assertSame(42, Config::get('system.count'));
        $this->assertIsInt(Config::get('system.count'));
    }

    public function test_all_returns_empty_array_when_no_config(): void
    {
        $this->assertSame([], Config::all());
    }

    public function test_loads_from_database_when_cache_disabled(): void
    {
        $this->app['config']->set('config-store.cache_enabled', false);
        $this->app->forgetInstance('config-store');
        Config::set('system.from_db', 'value');
        $this->app->forgetInstance('config-store');
        $this->app['config']->set('config-store.cache_enabled', false);

        $value = Config::get('system.from_db');
        $this->assertSame('value', $value);
    }

    public function test_put_cache_skipped_when_cache_disabled(): void
    {
        $this->app['config']->set('config-store.cache_enabled', false);
        $this->app->forgetInstance('config-store');
        Config::set('system.cache_off', 'x');
        Config::forget('system.cache_off');
        $this->assertFalse(Config::has('system.cache_off'));
    }

    public function test_fresh_does_not_update_in_memory(): void
    {
        Config::set('system.origin', 'first');
        Config::refresh();
        $record = ConfigModel::where('group', 'system')->where('key', 'origin')->first();
        $this->assertNotNull($record);
        /** @var ConfigModel $record */
        $record->value = 'updated';
        $record->save();
        $this->assertSame('updated', Config::fresh('system.origin'));
        $this->assertSame('first', Config::get('system.origin'));
    }

    public function test_get_returns_stored_null_instead_of_default(): void
    {
        Config::set('system.nullable', null);

        $this->assertNull(Config::get('system.nullable', 'fallback'));
        $this->assertTrue(Config::has('system.nullable'));
    }

    public function test_set_without_preload_does_not_clobber_existing_cache_map(): void
    {
        Config::set('system.first', 'one');
        Config::set('system.second', 'two');

        $this->resetConfigStoreService();

        Config::set('system.third', 'three');

        $this->assertSame('one', Config::get('system.first'));
        $this->assertSame('two', Config::get('system.second'));
        $this->assertSame('three', Config::get('system.third'));
    }

    public function test_forget_without_preload_invalidates_stale_cache(): void
    {
        Config::set('system.cached', 'value');

        $this->resetConfigStoreService();

        $this->assertTrue(Config::forget('system.cached'));
        $this->assertNull(Config::get('system.cached'));
    }

    public function test_forget_with_key_not_in_memory_still_clears_db(): void
    {
        ConfigModel::create([
            'group' => 'orphan',
            'key' => 'only_in_db',
            'value' => 'x',
            'type' => 'string',
        ]);
        $result = Config::forget('orphan.only_in_db');
        $this->assertTrue($result);
        Config::refresh();
        $this->assertNull(Config::get('orphan.only_in_db'));
    }

    public function test_unknown_type_returns_value_as_is(): void
    {
        ConfigModel::create([
            'group' => 'raw',
            'key' => 'custom',
            'value' => 'passthrough',
            'type' => 'unknown',
        ]);
        Config::refresh();
        $this->assertSame('passthrough', Config::get('raw.custom'));
    }

    public function test_cache_works_when_using_default_store(): void
    {
        $this->app['config']->set('config-store.cache_store', 'array');
        $this->app['config']->set('cache.default', 'array');
        $this->resetConfigStoreService();
        Config::set('system.via_default', 'ok');
        $this->assertSame('ok', Config::get('system.via_default'));
    }

    public function test_warm_instances_keep_shared_cache_coherent_via_version_stamp(): void
    {
        $serviceA = $this->makeConfigService();
        $serviceB = $this->makeConfigService();

        $serviceA->set('system.first', 'one');
        $serviceB->set('system.second', 'two');

        $serviceC = $this->makeConfigService();
        $serviceC->refresh();

        $this->assertSame('one', $serviceC->get('system.first'));
        $this->assertSame('two', $serviceC->get('system.second'));
    }

    public function test_set_with_invalid_type_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Config::set('system.bad', 'value', 'boolean');
    }

    public function test_set_with_invalid_json_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Config::set('system.bad_json', '{invalid', 'json');
    }

    public function test_remember_persists_default_when_missing(): void
    {
        $this->assertSame('default', Config::remember('system.remembered', 'default'));
        $this->assertSame('default', Config::get('system.remembered'));
    }

    public function test_typed_getters_return_expected_types(): void
    {
        Config::set('typed.name', 'XCrawler');
        Config::set('typed.count', 3);
        Config::set('typed.rate', 1.5);
        Config::set('typed.enabled', true);
        Config::set('typed.items', ['a' => 1], 'array');

        $this->assertSame('XCrawler', Config::getString('typed.name'));
        $this->assertSame(3, Config::getInt('typed.count'));
        $this->assertSame(1.5, Config::getFloat('typed.rate'));
        $this->assertSame(3.0, Config::getFloat('typed.count'));
        $this->assertTrue(Config::getBool('typed.enabled'));
        $this->assertSame(['a' => 1], Config::getArray('typed.items'));
    }

    public function test_forget_many_and_clear_bump_cache_once(): void
    {
        Config::set('system.a', '1');
        Config::set('system.b', '2');
        Config::set('system.c', '3');

        $versionKey = 'jooservices_config_all_test:version';
        $before = $this->cacheVersion($versionKey);

        $this->assertSame(2, Config::forgetMany(['system.a', 'system.b', 'system.missing']));
        $this->assertSame($before + 1, $this->cacheVersion($versionKey));

        $beforeClear = $this->cacheVersion($versionKey);
        $this->assertSame(1, Config::clear());
        $this->assertSame($beforeClear + 1, $this->cacheVersion($versionKey));
        $this->assertSame([], Config::all());
    }

    private function cacheVersion(string $versionKey): int
    {
        $value = Cache::store('array')->get($versionKey, 0);

        return is_numeric($value) ? (int) $value : 0;
    }

    public function test_forget_many_returns_zero_for_empty_input(): void
    {
        $this->assertSame(0, Config::forgetMany([]));
        $this->assertSame(0, Config::clear());
    }

    public function test_forget_many_rejects_empty_path(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Config::forgetMany(['']);
    }

    public function test_get_int_throws_when_type_mismatch(): void
    {
        Config::set('typed.wrong', 'text');

        $this->expectException(InvalidArgumentException::class);
        Config::getInt('typed.wrong');
    }

    public function test_refresh_with_cache_disabled_does_not_fail(): void
    {
        $this->app['config']->set('config-store.cache_enabled', false);
        $this->resetConfigStoreService();

        Config::set('system.cached', 'value');
        Config::refresh();

        $this->assertSame('value', Config::get('system.cached'));
    }

    public function test_container_resolves_config_store_contract(): void
    {
        $this->assertInstanceOf(ConfigService::class, $this->app->make(ConfigStore::class));
    }

    public function test_set_null_without_type_infers_null(): void
    {
        Config::set('system.null_inferred', null);
        $this->assertNull(Config::get('system.null_inferred'));
    }

    public function test_normalize_value_array_type_with_non_array_non_string(): void
    {
        ConfigModel::create([
            'group' => 'arr',
            'key' => 'as_int',
            'value' => 42,
            'type' => 'array',
        ]);
        Config::refresh();
        $this->assertSame([42], Config::get('arr.as_int'));

        ConfigModel::create([
            'group' => 'arr',
            'key' => 'as_bool',
            'value' => false,
            'type' => 'array',
        ]);
        Config::refresh();
        $this->assertSame([false], Config::get('arr.as_bool'));

        $this->assertSame([], Config::get('arr.missing', []));
    }

    public function test_normalize_value_array_type_with_already_array_from_mongo(): void
    {
        ConfigModel::create([
            'group' => 'arr',
            'key' => 'native',
            'value' => ['nested' => true],
            'type' => 'array',
        ]);
        Config::refresh();
        $this->assertSame(['nested' => true], Config::get('arr.native'));
    }

    public function test_remember_returns_existing_value_without_overwriting(): void
    {
        Config::set('system.existing', 'keep');

        $this->assertSame('keep', Config::remember('system.existing', 'replace'));
    }

    public function test_loaded_instance_reloads_when_cache_version_changes(): void
    {
        $serviceA = $this->makeConfigService();
        $serviceB = $this->makeConfigService();

        $serviceA->set('system.first', 'one');
        $serviceB->get('system.first');
        $serviceB->set('system.second', 'two');

        $this->assertSame('one', $serviceA->get('system.first'));
        $this->assertSame('two', $serviceA->get('system.second'));
    }

    public function test_poisoned_cache_entries_with_invalid_groups_are_ignored(): void
    {
        Config::set('valid.key', 'value');

        $cacheKey = config('config-store.cache_key');
        $this->assertIsString($cacheKey);

        Cache::store('array')->put($cacheKey, 'not-a-valid-encrypted-payload', 3600);

        $this->resetConfigStoreService();

        $this->assertSame('value', Config::get('valid.key'));
    }

    public function test_set_json_type_rejects_non_array_json_document(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON value must decode to an array');

        Config::set('system.scalar_json', '"just-a-string"', 'json');
    }

    public function test_set_array_type_rejects_values_that_cannot_be_json_encoded(): void
    {
        $resource = fopen('php://memory', 'rb');
        $this->assertIsResource($resource);

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Unable to encode value as JSON');

            Config::set('system.bad', ['stream' => $resource], 'array');
        } finally {
            fclose($resource);
        }
    }

    public function test_get_array_returns_default_when_key_missing(): void
    {
        $this->assertSame(['fallback' => true], Config::getArray('system.missing', ['fallback' => true]));
    }

    public function test_get_string_throws_when_type_mismatch(): void
    {
        Config::set('typed.count', 3);

        $this->expectException(InvalidArgumentException::class);
        Config::getString('typed.count');
    }

    public function test_get_array_throws_when_type_mismatch(): void
    {
        Config::set('typed.text', 'hello');

        $this->expectException(InvalidArgumentException::class);
        Config::getArray('typed.text', ['fallback' => true]);
    }

    public function test_custom_cache_version_key_is_honored(): void
    {
        $this->app['config']->set('config-store.cache_version_key', 'custom_config_version_test');
        $this->resetConfigStoreService();

        Config::set('system.versioned', 'ok');

        $this->assertNotSame(0, Cache::store('array')->get('custom_config_version_test'));
    }

    public function test_nested_path_uses_first_segment_as_group(): void
    {
        Config::set('mail.smtp.host', 'smtp.example.com');

        $this->assertSame('smtp.example.com', Config::get('mail.smtp.host'));
        $this->assertSame(['smtp.host' => 'smtp.example.com'], Config::group('mail'));
    }

    public function test_set_many_persists_entries_with_single_cycle(): void
    {
        Config::setMany([
            'system.site_name' => 'XCrawler',
            'payment.retry' => ['value' => '3', 'type' => 'int'],
        ]);

        $this->assertSame('XCrawler', Config::get('system.site_name'));
        $this->assertSame(3, Config::get('payment.retry'));
    }

    public function test_encrypted_type_round_trips_plaintext(): void
    {
        $faker = fake();
        $secret = $faker->password(16);

        Config::set('secrets.api_token', $secret, 'encrypted');

        $record = ConfigModel::where('group', 'secrets')->where('key', 'api_token')->first();
        $this->assertNotNull($record);
        $this->assertSame('encrypted', $record->type);
        $this->assertNotSame($secret, $record->value);
        $this->assertSame($secret, Config::get('secrets.api_token'));
    }

    public function test_list_ordered_normalizes_json_values(): void
    {
        Config::set('system.payload', ['foo' => 'bar'], 'json');

        $row = Config::listOrdered()->first(
            static fn(array $item): bool => $item['group'] === 'system' && $item['key'] === 'payload',
        );

        $this->assertNotNull($row);
        $this->assertSame(['foo' => 'bar'], $row['value']);
        $this->assertSame('json', $row['type']);
    }

    public function test_set_many_with_empty_entries_is_noop(): void
    {
        Config::setMany([]);
        $this->assertSame([], Config::all());
    }

    public function test_encrypted_decrypt_failure_throws(): void
    {
        ConfigModel::create([
            'group' => 'secrets',
            'key' => 'broken',
            'value' => 'not-a-valid-payload',
            'type' => 'encrypted',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to decrypt encrypted config value');

        Config::refresh();
        Config::get('secrets.broken');
    }

    public function test_ensure_indexes_returns_index_name(): void
    {
        $this->assertSame('config_group_key_unique', Config::ensureIndexes());
    }

    public function test_normalize_non_scalar_string_falls_back_to_empty(): void
    {
        ConfigModel::create([
            'group' => 'weird',
            'key' => 'obj',
            'value' => ['not' => 'scalar'],
            'type' => 'string',
        ]);
        Config::refresh();

        $this->assertSame('', Config::get('weird.obj'));
    }

    public function test_set_many_rejects_empty_path_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Config::setMany(['' => 'value']);
    }

    public function test_non_numeric_cache_ttl_falls_back_to_default(): void
    {
        $this->app['config']->set('config-store.cache_ttl', 'not-a-number');
        $this->resetConfigStoreService();

        Config::set('system.ttl', 'ok');
        $this->assertSame('ok', Config::get('system.ttl'));
    }

    public function test_set_many_validates_all_entries_before_writing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        try {
            Config::setMany([
                'system.ok' => 'kept-out',
                'system.' => 'invalid',
            ]);
        } finally {
            $this->assertFalse(Config::has('system.ok'));
        }
    }

    public function test_list_paths_returns_group_key_without_values(): void
    {
        Config::set('system.site_name', 'XCrawler');
        Config::set('payment.retry', 3);

        $paths = Config::listPaths()->map(
            static fn(array $row): string => $row['group'] . '.' . $row['key'],
        )->all();

        $this->assertSame(['payment.retry', 'system.site_name'], $paths);
    }

    public function test_shared_cache_payload_is_encrypted(): void
    {
        Config::set('secrets.api_token', 'super-secret', 'encrypted');

        $cacheKey = config('config-store.cache_key');
        $this->assertIsString($cacheKey);
        $cached = Cache::store('array')->get($cacheKey);

        $this->assertIsString($cached);
        $this->assertStringNotContainsString('super-secret', $cached);
        $this->assertSame('super-secret', Config::get('secrets.api_token'));
    }

    public function test_invalid_encrypted_cache_payload_falls_back_to_database(): void
    {
        Config::set('system.site_name', 'XCrawler');

        $cacheKey = config('config-store.cache_key');
        $this->assertIsString($cacheKey);
        Cache::store('array')->put($cacheKey, ['not' => 'encrypted'], 3600);

        $this->resetConfigStoreService();

        $this->assertSame('XCrawler', Config::get('system.site_name'));
    }

    public function test_warm_instance_reloads_when_remote_cache_version_changes(): void
    {
        $service = $this->makeConfigService();
        $service->set('system.first', 'one');

        $configuredVersionKey = config('config-store.cache_version_key');
        $cacheKey = config('config-store.cache_key');
        $this->assertIsString($cacheKey);
        $versionKey = is_string($configuredVersionKey) && $configuredVersionKey !== ''
            ? $configuredVersionKey
            : $cacheKey . ':version';

        Cache::store('array')->forget($cacheKey);
        Cache::store('array')->put($versionKey, 9999, 3600);

        ConfigModel::create([
            'group' => 'system',
            'key' => 'second',
            'value' => 'two',
            'type' => 'string',
        ]);

        $this->assertSame('two', $service->get('system.second'));
    }
}
