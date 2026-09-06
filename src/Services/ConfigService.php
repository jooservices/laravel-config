<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Services;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use JOOservices\LaravelConfig\Contracts\ConfigStore;
use JOOservices\LaravelConfig\Events\ConfigChanged;
use JOOservices\LaravelConfig\Events\ConfigForgotten;
use JOOservices\LaravelConfig\Models\Config as ConfigModel;
use JOOservices\LaravelConfig\Support\ConfigPath;
use JOOservices\LaravelConfig\Support\ConfigType;
use JsonException;
use Throwable;

class ConfigService implements ConfigStore
{
    /** @var array<string, array<string, mixed>> */
    protected array $items = [];

    protected bool $loaded = false;

    protected ?int $cacheVersion = null;

    public function __construct(
        protected Repository $cacheStore,
        protected Encrypter $encrypter,
    ) {
    }

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

    public function getString(string $path, ?string $default = null): ?string
    {
        return $this->typedGet($path, $default, static fn(mixed $value): ?string => is_string($value) ? $value : null);
    }

    public function getInt(string $path, ?int $default = null): ?int
    {
        return $this->typedGet($path, $default, static fn(mixed $value): ?int => is_int($value) ? $value : null);
    }

    public function getFloat(string $path, ?float $default = null): ?float
    {
        return $this->typedGet($path, $default, static fn(mixed $value): ?float => is_float($value) ? $value : null);
    }

    public function getBool(string $path, ?bool $default = null): ?bool
    {
        return $this->typedGet($path, $default, static fn(mixed $value): ?bool => is_bool($value) ? $value : null);
    }

    /**
     * @param  array<string, mixed>|null  $default
     * @return array<string, mixed>|null
     */
    public function getArray(string $path, ?array $default = null): ?array
    {
        return $this->typedGet($path, $default, function (mixed $value): ?array {
            if (! is_array($value)) {
                return null;
            }

            return $this->normalizeArrayMap($value);
        });
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

        return $this->normalizeStoredValue($record->value, $record->type ?? 'string');
    }

    public function set(string $path, mixed $value, ?string $type = null): void
    {
        $this->setMany([
            $path => [
                'value' => $value,
                'type' => $type,
            ],
        ]);
    }

    public function setMany(array $entries): void
    {
        if ($entries === []) {
            return;
        }

        $mutations = [];

        foreach ($entries as $path => $entry) {
            if (! is_string($path) || $path === '') {
                throw new InvalidArgumentException('Config path keys must be non-empty strings.');
            }

            [$value, $type] = $this->resolveEntry($entry);
            $configPath = $this->parsePath($path);
            $configType = $type === null ? ConfigType::infer($value) : ConfigType::parse($type);
            $storedValue = $this->valueForStorage($value, $configType);
            $normalizedValue = $this->normalizeValue(
                $configType === ConfigType::Encrypted ? $value : $storedValue,
                $configType,
            );

            ConfigModel::updateOrCreate(
                [
                    'group' => $configPath->group,
                    'key' => $configPath->key,
                ],
                [
                    'value' => $storedValue,
                    'type' => $configType->value,
                ],
            );

            $mutations[] = [
                'path' => $path,
                'group' => $configPath->group,
                'key' => $configPath->key,
                'normalized' => $normalizedValue,
                'type' => $configType,
            ];
        }

        $this->bumpCacheVersion();

        if (! $this->loaded) {
            $this->invalidateCache();
        } else {
            foreach ($mutations as $mutation) {
                $this->items[$mutation['group']][$mutation['key']] = $mutation['normalized'];
            }
            $this->putCache();
        }

        foreach ($mutations as $mutation) {
            $this->dispatchChanged(
                $mutation['path'],
                $mutation['normalized'],
                $mutation['type'],
            );
        }
    }

    public function remember(string $path, mixed $default, ?string $type = null): mixed
    {
        if ($this->has($path)) {
            return $this->get($path);
        }

        $this->set($path, $default, $type);

        return $this->get($path, $default);
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

        $this->bumpCacheVersion();

        if (! $this->loaded) {
            $this->invalidateCache();

            event(new ConfigForgotten($path));

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

        event(new ConfigForgotten($path));

        return true;
    }

    /**
     * @return Collection<int, array{group: string, key: string, value: mixed, type: string}>
     */
    public function listOrdered(): Collection
    {
        /** @var list<array{group: string, key: string, value: mixed, type: string}> $rows */
        $rows = [];

        foreach (
            ConfigModel::query()
                ->orderBy('group')
                ->orderBy('key')
                ->get() as $record
        ) {
            $type = $this->stringifyScalar($record->type ?? null);
            $resolvedType = $type === '' ? 'string' : $type;

            $rows[] = [
                'group' => $this->stringifyScalar($record->group ?? null),
                'key' => $this->stringifyScalar($record->key ?? null),
                'value' => $this->normalizeStoredValue($record->value ?? null, $resolvedType),
                'type' => $resolvedType,
            ];
        }

        return Collection::make($rows);
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
        $this->cacheVersion = null;
        $this->invalidateCache();
        $this->ensureLoaded();
    }

    protected function ensureLoaded(): void
    {
        if ($this->loaded) {
            if ($this->isCacheEnabled() && $this->getRemoteCacheVersion() !== $this->cacheVersion) {
                $this->loaded = false;
                $this->items = [];
                $this->cacheVersion = null;
            } else {
                return;
            }
        }

        if ($this->isCacheEnabled()) {
            $cached = $this->cacheStore->get($this->getCacheKey());
            if (is_array($cached)) {
                $this->items = $this->normalizeCachedItems($cached);
                $this->cacheVersion = $this->getRemoteCacheVersion();
                $this->loaded = true;

                return;
            }
        }

        $this->loadFromDatabase();
        $this->putCache();
        $this->cacheVersion = $this->getRemoteCacheVersion();
        $this->loaded = true;
    }

    protected function loadFromDatabase(): void
    {
        $this->items = [];

        foreach (ConfigModel::query()->get() as $record) {
            $group = (string) $record->group;
            $key = (string) $record->key;
            $this->items[$group] ??= [];
            $this->items[$group][$key] = $this->normalizeStoredValue(
                $record->value,
                $record->type ?? 'string',
            );
        }
    }

    public function ensureIndexes(): string
    {
        $indexName = 'config_group_key_unique';

        ConfigModel::query()->raw(function ($collection) use ($indexName) {
            return $collection->createIndex(
                ['group' => 1, 'key' => 1],
                ['name' => $indexName, 'unique' => true],
            );
        });

        return $indexName;
    }

    protected function putCache(): void
    {
        if (! $this->isCacheEnabled()) {
            return;
        }

        $this->cacheStore->put(
            $this->getCacheKey(),
            $this->items,
            $this->getCacheTtl(),
        );
    }

    protected function invalidateCache(): void
    {
        if (! $this->isCacheEnabled()) {
            return;
        }

        $this->cacheStore->forget($this->getCacheKey());
    }

    protected function bumpCacheVersion(): void
    {
        if (! $this->isCacheEnabled()) {
            return;
        }

        $version = $this->getRemoteCacheVersion() + 1;
        $this->cacheStore->put($this->getCacheVersionKey(), $version, $this->getCacheTtl());
        $this->cacheVersion = $version;
    }

    protected function getRemoteCacheVersion(): int
    {
        if (! $this->isCacheEnabled()) {
            return 0;
        }

        $value = $this->cacheStore->get($this->getCacheVersionKey(), 0);

        return is_numeric($value) ? (int) $value : 0;
    }

    protected function parsePath(string $path): ConfigPath
    {
        return ConfigPath::fromString($path);
    }

    protected function normalizeStoredValue(mixed $value, string $type): mixed
    {
        $configType = ConfigType::tryFrom($type);

        if ($configType === null) {
            return $value;
        }

        if ($configType === ConfigType::Encrypted) {
            return $this->decryptStoredValue($value);
        }

        return $this->normalizeValue($value, $configType);
    }

    /**
     * @param  array<mixed, mixed>  $cached
     * @return array<string, array<string, mixed>>
     */
    protected function normalizeCachedItems(array $cached): array
    {
        $items = [];

        foreach ($cached as $group => $keys) {
            if (! is_string($group) || ! is_array($keys)) {
                continue;
            }

            $items[$group] = [];

            foreach ($keys as $key => $value) {
                if (is_string($key)) {
                    $items[$group][$key] = $value;
                }
            }
        }

        return $items;
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function normalizeArrayValue(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            return $this->decodeJsonArray($value);
        }

        return (array) $value;
    }

    /**
     * @param  array<mixed, mixed>  $value
     * @return array<string, mixed>
     */
    protected function normalizeArrayMap(array $value): array
    {
        $result = [];

        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $result[$key] = $item;
            }
        }

        return $result;
    }

    protected function normalizeValue(mixed $value, ConfigType $type): mixed
    {
        return match ($type) {
            ConfigType::String => is_scalar($value) || $value === null ? (string) $value : '',
            ConfigType::Int => is_numeric($value) ? (int) $value : 0,
            ConfigType::Float => is_numeric($value) ? (float) $value : 0.0,
            ConfigType::Bool => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            ConfigType::Array => $this->normalizeArrayValue($value),
            ConfigType::Json => is_string($value)
                ? $this->decodeJsonArray($value)
                : (is_array($value) ? $this->normalizeArrayMap($value) : []),
            ConfigType::Null => null,
            ConfigType::Encrypted => is_scalar($value) || $value === null ? (string) $value : '',
        };
    }

    protected function valueForStorage(mixed $value, ConfigType $type): mixed
    {
        if ($type === ConfigType::Encrypted) {
            $plaintext = is_scalar($value) || $value === null ? (string) $value : '';

            return $this->encrypter->encryptString($plaintext);
        }

        if ($type === ConfigType::Array || $type === ConfigType::Json) {
            if (is_string($value)) {
                $this->decodeJsonArray($value);

                return $value;
            }

            return $this->encodeJson($value);
        }

        return $value;
    }

    protected function decryptStoredValue(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        try {
            return (string) $this->encrypter->decryptString($value);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                'Unable to decrypt encrypted config value: ' . $exception->getMessage(),
                0,
                $exception,
            );
        }
    }

    /**
     * @return array{0: mixed, 1: string|null}
     */
    protected function resolveEntry(mixed $entry): array
    {
        if (is_array($entry) && array_key_exists('value', $entry)) {
            $type = $entry['type'] ?? null;

            return [$entry['value'], is_string($type) ? $type : null];
        }

        return [$entry, null];
    }

    protected function dispatchChanged(string $path, mixed $normalizedValue, ConfigType $type): void
    {
        if ($type->isSensitive()) {
            event(new ConfigChanged($path, null, $type->value));

            return;
        }

        event(new ConfigChanged($path, $normalizedValue, $type->value));
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJsonArray(string $value): array
    {
        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Invalid JSON value: ' . $exception->getMessage(), 0, $exception);
        }

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('JSON value must decode to an array.');
        }

        $result = [];

        foreach ($decoded as $key => $item) {
            if (is_string($key)) {
                $result[$key] = $item;
            }
        }

        return $result;
    }

    protected function encodeJson(mixed $value): string
    {
        try {
            $encoded = json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException(
                'Unable to encode value as JSON: ' . $exception->getMessage(),
                0,
                $exception,
            );
        }

        return $encoded;
    }

    /**
     * @template T
     *
     * @param  callable(mixed): (?T)  $assert
     * @return T|null
     */
    protected function typedGet(string $path, mixed $default, callable $assert): mixed
    {
        if (! $this->has($path)) {
            return $default;
        }

        $value = $this->get($path);
        $typed = $assert($value);

        if ($typed === null && $value !== null) {
            throw new InvalidArgumentException(sprintf(
                'Config value at [%s] is not the expected type.',
                $path,
            ));
        }

        return $typed;
    }

    private function stringifyScalar(mixed $value): string
    {
        if (! is_scalar($value) && $value !== null) {
            return '';
        }

        return (string) $value;
    }

    protected function isCacheEnabled(): bool
    {
        return (bool) config('config-store.cache_enabled', true);
    }

    protected function getCacheKey(): string
    {
        $value = config('config-store.cache_key', 'jooservices_config_all');

        return is_string($value) ? $value : 'jooservices_config_all';
    }

    protected function getCacheVersionKey(): string
    {
        $configured = config('config-store.cache_version_key');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return $this->getCacheKey() . ':version';
    }

    protected function getCacheTtl(): int
    {
        $value = config('config-store.cache_ttl', 3600);

        return is_numeric($value) ? (int) $value : 3600;
    }
}
