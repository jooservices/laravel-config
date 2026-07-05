<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Console\Commands;

use InvalidArgumentException;
use JOOservices\LaravelConfig\Contracts\ConfigStore;
use JOOservices\LaravelConfig\Support\ConfigType;
use JsonException;

class ImportConfigCommand extends ConfigCommand
{
    protected $signature = 'config-store:import {file} {--merge}';

    protected $description = 'Import config-store values from JSON.';

    public function handle(ConfigStore $configService): int
    {
        $payload = $this->readImportPayload();

        if ($payload === null) {
            return self::FAILURE;
        }

        if (! (bool) $this->option('merge')) {
            $this->replaceExistingConfig($configService);
        }

        $imported = $this->importPayload($configService, $payload);

        $this->info(sprintf('Imported %d config value(s).', $imported));

        return self::SUCCESS;
    }

    /**
     * @return array<mixed, mixed>|null
     */
    private function readImportPayload(): ?array
    {
        $file = $this->fileArgument();

        if (! is_readable($file)) {
            $this->error('Import file is not readable.');

            return null;
        }

        $contents = file_get_contents($file);

        if ($contents === false) {
            $this->error('Unable to read import file.');

            return null;
        }

        try {
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->error('Invalid JSON import file: '.$exception->getMessage());

            return null;
        }

        if (! is_array($payload) || array_is_list($payload)) {
            $this->error('Import file must contain a JSON object.');

            return null;
        }

        return $payload;
    }

    private function replaceExistingConfig(ConfigStore $configService): void
    {
        foreach ($configService->all() as $group => $keys) {
            foreach (array_keys($keys) as $key) {
                $configService->forget($group.'.'.$key);
            }
        }
    }

    /**
     * @param  array<mixed, mixed>  $payload
     */
    private function importPayload(ConfigStore $configService, array $payload): int
    {
        $imported = 0;

        foreach ($payload as $group => $keys) {
            if (! is_string($group) || ! is_array($keys)) {
                continue;
            }

            $imported += $this->importGroup($configService, $group, $keys);
        }

        return $imported;
    }

    /**
     * @param  array<mixed, mixed>  $keys
     */
    private function importGroup(ConfigStore $configService, string $group, array $keys): int
    {
        $imported = 0;

        foreach ($keys as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if ($this->importEntry($configService, $group, $key, $value)) {
                $imported++;
            }
        }

        return $imported;
    }

    private function importEntry(ConfigStore $configService, string $group, string $key, mixed $value): bool
    {
        try {
            [$valueToStore, $type] = $this->resolveImportValue($value);
            $configService->set(
                $group.'.'.$key,
                $valueToStore,
                $type ?? ConfigType::infer($valueToStore)->value
            );

            return true;
        } catch (InvalidArgumentException $exception) {
            $this->error(sprintf('Skipped %s.%s: %s', $group, $key, $exception->getMessage()));

            return false;
        }
    }

    /**
     * @return array{0: mixed, 1: string|null}
     */
    private function resolveImportValue(mixed $value): array
    {
        if (! is_array($value) || ! array_key_exists('__type', $value) || ! array_key_exists('__value', $value)) {
            return [$value, null];
        }

        $type = is_string($value['__type']) ? $value['__type'] : null;

        return [$value['__value'], $type];
    }
}
