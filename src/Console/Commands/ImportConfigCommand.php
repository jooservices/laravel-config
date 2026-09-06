<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Console\Commands;

use InvalidArgumentException;
use JOOservices\LaravelConfig\Contracts\ConfigStore;
use JOOservices\LaravelConfig\Support\ConfigType;
use JsonException;

class ImportConfigCommand extends ConfigCommand
{
    protected $signature = 'config-store:import {file} {--merge} {--dry-run} {--force}';

    protected $description = 'Import config-store values from JSON.';

    public function handle(ConfigStore $configService): int
    {
        $payload = $this->readImportPayload();

        if ($payload === null) {
            return self::FAILURE;
        }

        $merge = (bool) $this->option('merge');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if (! $merge && ! $force && ! $dryRun) {
            $this->error('Replacing all config values requires --force (or pass --merge / --dry-run).');

            return self::FAILURE;
        }

        $entries = $this->flattenPayload($payload);

        if ($dryRun) {
            $this->info(sprintf(
                'Dry-run: would %s and import %d config value(s).',
                $merge ? 'merge' : 'replace existing values',
                count($entries),
            ));

            return self::SUCCESS;
        }

        if (! $merge) {
            $this->replaceExistingConfig($configService);
        }

        $configService->setMany($entries);

        $this->info(sprintf('Imported %d config value(s).', count($entries)));

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
            $this->error('Invalid JSON import file: ' . $exception->getMessage());

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
        $configService->clear();
    }

    /**
     * @param  array<mixed, mixed>  $payload
     * @return array<string, array{value: mixed, type: string|null}>
     */
    private function flattenPayload(array $payload): array
    {
        $entries = [];

        foreach ($payload as $group => $keys) {
            if (! is_string($group) || ! is_array($keys)) {
                continue;
            }

            foreach ($keys as $key => $value) {
                if (! is_string($key) || $key === '') {
                    continue;
                }

                try {
                    [$valueToStore, $type] = $this->resolveImportValue($value);
                    $resolvedType = $type ?? ConfigType::infer($valueToStore)->value;
                    $this->assertImportValueEncodable($valueToStore, $resolvedType);
                    $entries[$group . '.' . $key] = [
                        'value' => $valueToStore,
                        'type' => $resolvedType,
                    ];
                } catch (InvalidArgumentException $exception) {
                    $this->error(sprintf('Skipped %s.%s: %s', $group, $key, $exception->getMessage()));
                }
            }
        }

        return $entries;
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

    private function assertImportValueEncodable(mixed $value, string $type): void
    {
        $configType = ConfigType::parse($type);

        if ($configType !== ConfigType::Array && $configType !== ConfigType::Json) {
            return;
        }

        if (is_string($value)) {
            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException('Invalid JSON value: ' . $exception->getMessage(), 0, $exception);
            }

            if (! is_array($decoded)) {
                throw new InvalidArgumentException('JSON value must decode to an array.');
            }

            return;
        }

        try {
            json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException(
                'Unable to encode value as JSON: ' . $exception->getMessage(),
                0,
                $exception,
            );
        }
    }
}
