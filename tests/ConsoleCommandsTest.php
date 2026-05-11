<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Tests;

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
}
