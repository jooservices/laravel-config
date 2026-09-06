<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Tests;

use Illuminate\Database\DatabaseManager;
use JOOservices\LaravelConfig\Facades\Config;
use JOOservices\LaravelConfig\Models\Config as ConfigModel;

class ConsoleCommandsTest extends TestCase
{
    public function test_get_command_outputs_array_without_array_to_string_warning(): void
    {
        Config::set('system.payload', ['foo' => 'bar'], 'array');

        $this->artisan('config-store:get', ['path' => 'system.payload'])
            ->expectsOutput('{"foo":"bar"}')
            ->assertSuccessful();
    }

    public function test_get_command_outputs_existing_value(): void
    {
        Config::set('system.site_name', 'XCrawler');

        $this->artisan('config-store:get', ['path' => 'system.site_name'])
            ->expectsOutput('XCrawler')
            ->assertSuccessful();
    }

    public function test_get_command_outputs_default_value(): void
    {
        $this->artisan('config-store:get', ['path' => 'system.missing', '--default' => 'fallback'])
            ->expectsOutput('fallback')
            ->assertSuccessful();
    }

    public function test_set_command_persists_value(): void
    {
        $this->artisan('config-store:set', ['path' => 'system.enabled', 'value' => 'true', '--type' => 'bool'])
            ->expectsOutput('Config value stored.')
            ->assertSuccessful();

        $this->resetConfigStoreService();

        $this->assertTrue(Config::get('system.enabled'));
    }

    public function test_forget_command_removes_value(): void
    {
        Config::set('system.temp', 'value');

        $this->artisan('config-store:forget', ['path' => 'system.temp'])
            ->expectsOutput('Config value removed.')
            ->assertSuccessful();

        $this->resetConfigStoreService();

        $this->assertFalse(Config::has('system.temp'));
    }

    public function test_forget_command_reports_invalid_path(): void
    {
        $this->artisan('config-store:forget', ['path' => 'invalid'])
            ->expectsOutput('Invalid config path: invalid. Must be group.key')
            ->assertFailed();
    }

    public function test_refresh_command_reloads_from_storage(): void
    {
        Config::set('system.site_name', 'Before');
        $record = ConfigModel::where('group', 'system')->where('key', 'site_name')->first();
        $this->assertNotNull($record);
        /** @var ConfigModel $record */
        $record->value = 'After';
        $record->save();

        $this->artisan('config-store:refresh')
            ->expectsOutput('Config store refreshed.')
            ->assertSuccessful();

        $this->resetConfigStoreService();

        $this->assertSame('After', Config::get('system.site_name'));
    }

    public function test_set_command_rejects_invalid_type(): void
    {
        $this->artisan('config-store:set', ['path' => 'system.enabled', 'value' => 'true', '--type' => 'boolean'])
            ->expectsOutputToContain('Unsupported config type')
            ->assertFailed();
    }

    public function test_list_command_outputs_values(): void
    {
        Config::set('system.site_name', 'XCrawler');

        $this->artisan('config-store:list', ['group' => 'system'])
            ->expectsOutputToContain('system.site_name = XCrawler')
            ->assertSuccessful();
    }

    public function test_list_command_json_output(): void
    {
        Config::set('system.site_name', 'XCrawler');

        $this->artisan('config-store:list', ['group' => 'system', '--json' => true])
            ->expectsOutputToContain('"site_name": "XCrawler"')
            ->assertSuccessful();
    }

    public function test_doctor_command_passes_on_healthy_store(): void
    {
        Config::set('system.site_name', 'XCrawler');

        $this->artisan('config-store:ensure-index')->assertSuccessful();

        $this->artisan('config-store:doctor')
            ->expectsOutputToContain('Config store doctor passed all checks.')
            ->assertSuccessful();
    }

    public function test_export_and_import_round_trip(): void
    {
        Config::set('system.site_name', 'XCrawler');
        Config::set('system.enabled', true, 'bool');

        $file = sys_get_temp_dir() . '/config-store-export-' . uniqid('', true) . '.json';

        $this->artisan('config-store:export', ['file' => $file])
            ->expectsOutputToContain('Config values exported')
            ->assertSuccessful();

        Config::forget('system.site_name');
        Config::forget('system.enabled');

        $this->artisan('config-store:import', ['file' => $file, '--merge' => true])
            ->expectsOutputToContain('Imported')
            ->assertSuccessful();

        $this->resetConfigStoreService();

        $this->assertSame('XCrawler', Config::get('system.site_name'));
        $this->assertTrue(Config::get('system.enabled'));

        unlink($file);
    }

    public function test_export_command_writes_to_stdout(): void
    {
        Config::set('system.site_name', 'XCrawler');

        $this->artisan('config-store:export')
            ->expectsOutputToContain('"site_name": "XCrawler"')
            ->assertSuccessful();
    }

    public function test_import_command_rejects_unreadable_file(): void
    {
        $this->artisan('config-store:import', ['file' => '/path/does/not/exist.json'])
            ->expectsOutputToContain('Import file is not readable.')
            ->assertFailed();
    }

    public function test_list_command_reports_empty_group(): void
    {
        $this->artisan('config-store:list', ['group' => 'missing'])
            ->expectsOutput('No config values found.')
            ->assertSuccessful();
    }

    public function test_export_command_writes_file_successfully(): void
    {
        Config::set('system.site_name', 'XCrawler');

        $file = sys_get_temp_dir() . '/config-store-export-success-' . uniqid('', true) . '.json';

        $this->artisan('config-store:export', ['file' => $file])
            ->expectsOutputToContain('Config values exported to ' . $file)
            ->assertSuccessful();

        $this->assertFileExists($file);
        $this->assertStringContainsString('XCrawler', (string) file_get_contents($file));

        unlink($file);
    }

    public function test_ensure_index_command_creates_unique_group_key_index(): void
    {
        $this->artisan('config-store:ensure-index')
            ->expectsOutput('Ensured MongoDB index [config_group_key_unique].')
            ->assertSuccessful();

        $indexes = (array) ConfigModel::query()->raw(function ($collection): array {
            $indexNames = [];

            foreach ($collection->listIndexes() as $index) {
                $indexNames[] = $index->getName();
            }

            return $indexNames;
        });

        $this->assertContains('config_group_key_unique', $indexes);
    }

    public function test_forget_command_reports_missing_value(): void
    {
        $this->artisan('config-store:forget', ['path' => 'system.missing'])
            ->expectsOutput('Config value not found.')
            ->assertFailed();
    }

    public function test_get_command_outputs_bool_and_null_values(): void
    {
        Config::set('system.enabled', false, 'bool');
        Config::set('system.optional', null, 'null');

        $this->artisan('config-store:get', ['path' => 'system.enabled'])
            ->expectsOutput('false')
            ->assertSuccessful();

        $this->artisan('config-store:get', ['path' => 'system.optional'])
            ->expectsOutput('null')
            ->assertSuccessful();
    }

    public function test_get_command_formats_externally_seeded_object_values(): void
    {
        ConfigModel::create([
            'group' => 'external',
            'key' => 'object',
            'value' => ['nested' => 'value'],
            'type' => 'unknown',
        ]);
        Config::refresh();

        $this->artisan('config-store:get', ['path' => 'external.object'])
            ->expectsOutputToContain('nested')
            ->assertSuccessful();
    }

    public function test_list_command_outputs_all_groups_and_value_types(): void
    {
        Config::set('system.site_name', 'XCrawler');
        Config::set('system.enabled', true, 'bool');
        Config::set('system.optional', null, 'null');
        Config::set('system.payload', ['a' => 1], 'array');

        $this->artisan('config-store:list')
            ->expectsOutputToContain('system.site_name = XCrawler')
            ->expectsOutputToContain('system.enabled = true')
            ->expectsOutputToContain('system.optional = null')
            ->expectsOutputToContain('system.payload = {"a":1}')
            ->assertSuccessful();
    }

    public function test_export_command_filters_by_group(): void
    {
        Config::set('system.site_name', 'XCrawler');
        Config::set('payment.gateway', 'stripe');

        $this->artisan('config-store:export', ['--group' => 'system'])
            ->expectsOutputToContain('"site_name": "XCrawler"')
            ->doesntExpectOutputToContain('gateway')
            ->assertSuccessful();
    }

    public function test_export_command_reports_unwritable_target(): void
    {
        Config::set('system.site_name', 'XCrawler');

        $this->artisan('config-store:export', ['file' => sys_get_temp_dir()])
            ->expectsOutputToContain('Unable to write export file.')
            ->assertFailed();
    }

    public function test_export_command_fails_when_values_are_not_json_encodable(): void
    {
        Config::set('system.nan', NAN, 'float');

        $this->artisan('config-store:export')
            ->expectsOutputToContain('Unable to encode config values as JSON')
            ->assertFailed();
    }

    public function test_import_command_rejects_invalid_json_payload(): void
    {
        $file = sys_get_temp_dir() . '/config-store-invalid-' . uniqid('', true) . '.json';
        file_put_contents($file, '{invalid');

        $this->artisan('config-store:import', ['file' => $file])
            ->expectsOutputToContain('Invalid JSON import file')
            ->assertFailed();

        unlink($file);
    }

    public function test_import_command_rejects_non_object_json_payload(): void
    {
        $file = sys_get_temp_dir() . '/config-store-array-' . uniqid('', true) . '.json';
        file_put_contents($file, '["not","an","object"]');

        $this->artisan('config-store:import', ['file' => $file])
            ->expectsOutputToContain('Import file must contain a JSON object.')
            ->assertFailed();

        unlink($file);
    }

    public function test_import_command_replace_mode_clears_existing_values(): void
    {
        Config::set('system.keep', 'old');
        Config::set('system.remove', 'gone');

        $file = sys_get_temp_dir() . '/config-store-replace-' . uniqid('', true) . '.json';
        file_put_contents($file, json_encode(['system' => ['keep' => 'new']], JSON_THROW_ON_ERROR));

        $this->artisan('config-store:import', ['file' => $file, '--force' => true])
            ->expectsOutputToContain('Imported 1 config value(s).')
            ->assertSuccessful();

        $this->resetConfigStoreService();

        $this->assertSame('new', Config::get('system.keep'));
        $this->assertNull(Config::get('system.remove'));

        unlink($file);
    }

    public function test_import_command_requires_force_for_replace_mode(): void
    {
        $file = sys_get_temp_dir() . '/config-store-force-' . uniqid('', true) . '.json';
        file_put_contents($file, json_encode(['system' => ['keep' => 'new']], JSON_THROW_ON_ERROR));

        $this->artisan('config-store:import', ['file' => $file])
            ->expectsOutputToContain('requires --force')
            ->assertFailed();

        unlink($file);
    }

    public function test_import_command_dry_run_does_not_write(): void
    {
        Config::set('system.keep', 'old');

        $file = sys_get_temp_dir() . '/config-store-dry-' . uniqid('', true) . '.json';
        file_put_contents($file, json_encode(['system' => ['keep' => 'new']], JSON_THROW_ON_ERROR));

        $this->artisan('config-store:import', ['file' => $file, '--dry-run' => true])
            ->expectsOutputToContain('Dry-run:')
            ->assertSuccessful();

        $this->assertSame('old', Config::get('system.keep'));

        unlink($file);
    }

    public function test_import_command_skips_invalid_entries_and_invalid_json_values(): void
    {
        $file = sys_get_temp_dir() . '/config-store-partial-' . uniqid('', true) . '.json';
        file_put_contents($file, json_encode([
            123 => ['ignored' => 'value'],
            'system' => [
                'valid' => 'ok',
                'bad_json' => [
                    '__type' => 'json',
                    '__value' => '{invalid',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->artisan('config-store:import', ['file' => $file, '--merge' => true])
            ->expectsOutputToContain('Skipped system.bad_json')
            ->expectsOutputToContain('Imported 1 config value(s).')
            ->assertSuccessful();

        $this->resetConfigStoreService();

        $this->assertSame('ok', Config::get('system.valid'));

        unlink($file);
    }

    public function test_doctor_command_fails_when_unknown_types_exist(): void
    {
        ConfigModel::create([
            'group' => 'bad',
            'key' => 'type',
            'value' => 'x',
            'type' => 'unsupported',
        ]);

        $this->artisan('config-store:doctor')
            ->expectsOutputToContain('[fail] Unknown config types')
            ->expectsOutputToContain('Config store doctor found issues.')
            ->assertFailed();
    }

    public function test_doctor_command_skips_cache_round_trip_when_cache_disabled(): void
    {
        $this->app['config']->set('config-store.cache_enabled', false);
        $this->resetConfigStoreService();

        Config::set('system.site_name', 'XCrawler');

        $this->artisan('config-store:ensure-index')->assertSuccessful();

        $this->artisan('config-store:doctor')
            ->expectsOutputToContain('[ok] Cache round-trip')
            ->assertSuccessful();
    }

    public function test_doctor_command_reports_cache_failures_for_invalid_store(): void
    {
        $this->app['config']->set('config-store.cache_store', 'missing-store-name');

        $this->artisan('config-store:doctor')
            ->expectsOutputToContain('[fail] Cache round-trip')
            ->assertFailed();
    }

    public function test_ensure_index_command_reports_failure_when_mongodb_is_unreachable(): void
    {
        $originalDsn = config('database.connections.mongodb.dsn');
        $this->app['config']->set(
            'database.connections.mongodb.dsn',
            'mongodb://127.0.0.1:65530/?serverSelectionTimeoutMS=500',
        );
        /** @var DatabaseManager $db */
        $db = $this->app['db'];
        $db->purge('mongodb');
        $this->resetConfigStoreService();

        try {
            $this->artisan('config-store:ensure-index')
                ->expectsOutputToContain('Failed to ensure MongoDB index')
                ->assertFailed();
        } finally {
            $this->app['config']->set('database.connections.mongodb.dsn', $originalDsn);
            $db->purge('mongodb');
            $this->resetConfigStoreService();
        }
    }

    public function test_import_command_skips_json_scalar_document(): void
    {
        $file = sys_get_temp_dir() . '/config-store-scalar-json-' . uniqid('', true) . '.json';
        file_put_contents($file, json_encode([
            'system' => [
                'bad' => [
                    '__type' => 'json',
                    '__value' => '"scalar"',
                ],
                'ok' => 'kept',
            ],
        ], JSON_THROW_ON_ERROR));

        $this->artisan('config-store:import', ['file' => $file, '--merge' => true])
            ->expectsOutputToContain('Skipped system.bad')
            ->expectsOutputToContain('Imported 1 config value(s).')
            ->assertSuccessful();

        $this->assertSame('kept', Config::get('system.ok'));

        unlink($file);
    }

    public function test_doctor_command_passes_when_cache_disabled(): void
    {
        $this->app['config']->set('config-store.cache_enabled', false);
        $this->resetConfigStoreService();

        $this->artisan('config-store:doctor')
            ->expectsOutputToContain('Config store doctor passed all checks.')
            ->assertSuccessful();
    }

    public function test_list_command_reports_empty_store(): void
    {
        $this->artisan('config-store:list')
            ->expectsOutputToContain('No config values found.')
            ->assertSuccessful();
    }
}
