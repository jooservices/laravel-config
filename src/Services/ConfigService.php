<?php

declare(strict_types=1);

namespace JooServices\LaravelConfig\Services;

use Illuminate\Contracts\Cache\Repository;
use InvalidArgumentException;
use JooServices\LaravelConfig\Models\Config as ConfigModel;

class ConfigService
{
    protected array $items = [];

    protected bool $loaded = false;

    public function get(string $path, mixed $default = null): mixed
    {
        $this->ensureLoaded();

        $keys = $this->parsePath($path);

        $group = $keys['group'];
        $key = $keys['key'];

        $value = $this->items[$group][$key] ?? null;

        return $value !== null ? $value : $default;
    }

    public function fresh(string $path, mixed $default = null): mixed
    {
        $keys = $this->parsePath($path);
        $group = $keys['group'];
        $key = $keys['key'];

        $record = ConfigModel::where('group', $group)->where('key', $key)->first();

        if ($record === null) {
            return $default;
        }

        $normalized = $this->normalizeValue($record->value, $record->type ?? 'string');

        $this->items[$group][$key] = $normalized;

        return $normalized;
    }

    public function set(string $path, mixed $value, ?string $type = null): void
    {
        $keys = $this->parsePath($path);
        $group = $keys['group'];
        $key = $keys['key'];

        $type = $type ?? $this->inferType($value);
        $storedValue = $this->valueForStorage($value, $type);

        ConfigModel::updateOrCreate(
            [
                'group' => $group,
                'key' => $key,
            ],
            [
                'value' => $storedValue,
                'type' => $type,
            ]
        );

        $this->items[$group][$key] = $this->normalizeValue($storedValue, $type);
        $this->putCache();
    }

    public function has(string $path): bool
    {
        $this->ensureLoaded();
        $keys = $this->parsePath($path);
        $group = $keys['group'];
        $key = $keys['key'];

        return isset($this->items[$group][$key]);
    }

    public function forget(string $path): bool
    {
        $keys = $this->parsePath($path);
        $group = $keys['group'];
        $key = $keys['key'];

        $deleted = ConfigModel::where('group', $group)->where('key', $key)->delete() > 0;

        if ($deleted && isset($this->items[$group][$key])) {
            unset($this->items[$group][$key]);
            $this->putCache();
        }

        return $deleted;
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
        $this->items = [];

        $records = ConfigModel::all();

        foreach ($records as $record) {
            $group = (string) $record->group;
            $key = (string) $record->key;
            if (! isset($this->items[$group])) {
                $this->items[$group] = [];
            }
            $this->items[$group][$key] = $this->normalizeValue(
                $record->value,
                $record->type ?? 'string'
            );
        }
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

    protected function parsePath(string $path): array
    {
        $path = trim($path);

        if ($path === '' || $path[0] === '.' || substr($path, -1) === '.') {
            throw new InvalidArgumentException("Invalid config path: {$path}");
        }

        $parts = explode('.', $path);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new InvalidArgumentException("Invalid config path: {$path}. Must be group.key");
        }

        return [
            'group' => $parts[0],
            'key' => $parts[1],
        ];
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
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return 'bool';
        }
        if (is_int($value)) {
            return 'int';
        }
        if (is_float($value)) {
            return 'float';
        }
        if (is_array($value)) {
            return 'array';
        }

        return 'string';
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
