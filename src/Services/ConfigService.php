<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Services;

use Illuminate\Contracts\Cache\Repository;
use JOOservices\LaravelConfig\Models\Config as ConfigModel;
use JOOservices\LaravelConfig\Support\ConfigPath;

class ConfigService
{
    protected array $items = [];

    protected bool $loaded = false;

    public function get(string $path, mixed $default = null): mixed
    {
        $configPath = $this->parsePath($path);

        $this->ensureLoaded();

        if (
            ! array_key_exists($configPath->group, $this->items)
            || ! array_key_exists($configPath->key, $this->items[$configPath->group])
        ) {
            return $default;
        }

        return $this->items[$configPath->group][$configPath->key];
    }

    public function fresh(string $path, mixed $default = null): mixed
    {
        $configPath = $this->parsePath($path);

        $record = ConfigModel::query()
            ->where('group', $configPath->group)
            ->where('key', $configPath->key)
            ->first();

        if ($record === null) {
            return $default;
        }

        return $this->normalizeValue($record->value, $record->type ?? 'string');
    }

    public function set(string $path, mixed $value, ?string $type = null): void
    {
        $configPath = $this->parsePath($path);

        $type = $type ?? $this->inferType($value);
        $storedValue = $this->valueForStorage($value, $type);
        $normalizedValue = $this->normalizeValue($storedValue, $type);

        ConfigModel::updateOrCreate(
            [
                'group' => $configPath->group,
                'key' => $configPath->key,
            ],
            [
                'value' => $storedValue,
                'type' => $type,
            ]
        );

        if (! $this->loaded) {
            $this->invalidateCache();

            return;
        }

        $this->items[$configPath->group][$configPath->key] = $normalizedValue;
        $this->putCache();
    }

    public function has(string $path): bool
    {
        $configPath = $this->parsePath($path);

        $this->ensureLoaded();

        return array_key_exists($configPath->group, $this->items)
            && array_key_exists($configPath->key, $this->items[$configPath->group]);
    }

    public function forget(string $path): bool
    {
        $configPath = $this->parsePath($path);

        $deleted = ConfigModel::query()
            ->where('group', $configPath->group)
            ->where('key', $configPath->key)
            ->delete() > 0;

        if (! $deleted) {
            return false;
        }

        if (! $this->loaded) {
            $this->invalidateCache();

            return true;
        }

        if (
            array_key_exists($configPath->group, $this->items)
            && array_key_exists($configPath->key, $this->items[$configPath->group])
        ) {
            unset($this->items[$configPath->group][$configPath->key]);

            if ($this->items[$configPath->group] === []) {
                unset($this->items[$configPath->group]);
            }
        }

        $this->putCache();

        return true;
    }

    public function group(string $group): array
    {
        $this->ensureLoaded();

        return $this->items[$group] ?? [];
    }

    public function all(): array
    {
        $this->ensureLoaded();

        return $this->items;
    }

    public function refresh(): void
    {
        $this->loaded = false;
        $this->items = [];
        $this->getCacheStore()->forget($this->getCacheKey());
        $this->ensureLoaded();
    }

    protected function ensureLoaded(): void
    {
        if ($this->loaded) {
            return;
        }

        if ($this->isCacheEnabled()) {
            $cached = $this->getCacheStore()->get($this->getCacheKey());
            if (is_array($cached)) {
                $this->items = $cached;
                $this->loaded = true;

                return;
            }
        }

        $this->loadFromDatabase();
        $this->putCache();
        $this->loaded = true;
    }

    protected function loadFromDatabase(): void
    {
        $this->items = ConfigModel::query()
            ->get()
            ->reduce(function (array $items, ConfigModel $record): array {
                $group = (string) $record->group;
                $key = (string) $record->key;
                $items[$group] ??= [];
                $items[$group][$key] = $this->normalizeValue(
                    $record->value,
                    $record->type ?? 'string'
                );

                return $items;
            }, []);
    }

    public function ensureIndexes(): string
    {
        $indexName = 'config_group_key_unique';

        ConfigModel::query()->raw(function ($collection) use ($indexName) {
            return $collection->createIndex(
                ['group' => 1, 'key' => 1],
                ['name' => $indexName, 'unique' => true]
            );
        });

        return $indexName;
    }

    protected function putCache(): void
    {
        if (! $this->isCacheEnabled()) {
            return;
        }

        $this->getCacheStore()->put(
            $this->getCacheKey(),
            $this->items,
            $this->getCacheTtl()
        );
    }

    protected function invalidateCache(): void
    {
        if (! $this->isCacheEnabled()) {
            return;
        }

        $this->getCacheStore()->forget($this->getCacheKey());
    }

    protected function parsePath(string $path): ConfigPath
    {
        return ConfigPath::fromString($path);
    }

    protected function normalizeArrayValue(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            return (array) json_decode($value, true);
        }

        return (array) $value;
    }

    protected function normalizeValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'string' => (string) $value,
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array' => $this->normalizeArrayValue($value),
            'json' => is_string($value) ? (array) json_decode($value, true) : (array) $value,
            'null' => null,
            default => $value,
        };
    }

    protected function inferType(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => 'bool',
            is_int($value) => 'int',
            is_float($value) => 'float',
            is_array($value) => 'array',
            default => 'string',
        };
    }

    protected function valueForStorage(mixed $value, string $type): mixed
    {
        if ($type === 'array' || $type === 'json') {
            return is_string($value) ? $value : json_encode($value);
        }

        return $value;
    }

    protected function isCacheEnabled(): bool
    {
        return (bool) config('config-store.cache_enabled', true);
    }

    protected function getCacheKey(): string
    {
        return config('config-store.cache_key', 'jooservices_config_all');
    }

    protected function getCacheTtl(): int
    {
        return (int) config('config-store.cache_ttl', 3600);
    }

    protected function getCacheStore(): Repository
    {
        $store = config('config-store.cache_store');

        return cache()->store($store ?? config('cache.default', 'array'));
    }
}
