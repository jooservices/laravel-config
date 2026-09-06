<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Testing;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use JOOservices\LaravelConfig\Contracts\ConfigStore;
use JOOservices\LaravelConfig\Events\ConfigChanged;
use JOOservices\LaravelConfig\Events\ConfigForgotten;
use JOOservices\LaravelConfig\Support\ConfigPath;
use JOOservices\LaravelConfig\Support\ConfigType;
use JsonException;

class FakeConfigStore implements ConfigStore
{
    /** @var array<string, array<string, mixed>> */
    protected array $items = [];

    /** @var array<string, string> */
    protected array $types = [];

    /**
     * @param  array<string, array<string, mixed>>  $seed
     */
    public function __construct(array $seed = [])
    {
        foreach ($seed as $group => $keys) {
            if (! is_string($group) || ! is_array($keys)) {
                continue;
            }

            foreach ($keys as $key => $value) {
                if (! is_string($key)) {
                    continue;
                }

                $configType = ConfigType::infer($value);
                $this->items[$group][$key] = $this->normalizeValue($value, $configType);
                $this->types[$group . '.' . $key] = $configType->value;
            }
        }
    }

    public function get(string $path, mixed $default = null): mixed
    {
        $configPath = $this->parsePath($path);

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
        return $this->typedGet(
            $path,
            $default,
            static fn(mixed $value): ?float => is_int($value) || is_float($value) ? (float) $value : null,
        );
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
        return $this->get($path, $default);
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

            if (is_array($entry) && array_key_exists('value', $entry)) {
                $value = $entry['value'];
                $type = $entry['type'] ?? null;
            } else {
                $value = $entry;
                $type = null;
            }

            $configPath = $this->parsePath($path);
            $configType = is_string($type)
                ? ConfigType::parse($type)
                : ConfigType::infer($value);

            $normalized = $this->normalizeValue($value, $configType);
            $normalizedPath = $configPath->group . '.' . $configPath->key;
            $this->items[$configPath->group] ??= [];
            $this->items[$configPath->group][$configPath->key] = $normalized;
            $this->types[$normalizedPath] = $configType->value;

            $mutations[] = [
                'path' => $normalizedPath,
                'normalized' => $normalized,
                'type' => $configType,
            ];
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

        return array_key_exists($configPath->group, $this->items)
            && array_key_exists($configPath->key, $this->items[$configPath->group]);
    }

    public function forget(string $path): bool
    {
        return $this->forgetMany([$path]) === 1;
    }

    public function forgetMany(array $paths): int
    {
        if ($paths === []) {
            return 0;
        }

        $forgotten = [];

        foreach ($paths as $path) {
            if (! is_string($path) || $path === '') {
                throw new InvalidArgumentException('Config path keys must be non-empty strings.');
            }

            $configPath = $this->parsePath($path);
            $normalized = $configPath->group . '.' . $configPath->key;

            if (isset($forgotten[$normalized])) {
                continue;
            }

            if (
                ! array_key_exists($configPath->group, $this->items)
                || ! array_key_exists($configPath->key, $this->items[$configPath->group])
            ) {
                continue;
            }

            unset($this->items[$configPath->group][$configPath->key], $this->types[$normalized]);

            if ($this->items[$configPath->group] === []) {
                unset($this->items[$configPath->group]);
            }

            $forgotten[$normalized] = true;
        }

        foreach (array_keys($forgotten) as $normalizedPath) {
            event(new ConfigForgotten($normalizedPath));
        }

        return count($forgotten);
    }

    public function clear(): int
    {
        $paths = array_values(
            $this->listPaths()
                ->map(static fn(array $row): string => $row['group'] . '.' . $row['key'])
                ->all(),
        );

        return $this->forgetMany($paths);
    }

    /**
     * @return Collection<int, array{group: string, key: string}>
     */
    public function listPaths(): Collection
    {
        return collect($this->items)
            ->flatMap(function (array $keys, string $group): array {
                $records = [];

                foreach (array_keys($keys) as $key) {
                    if (! is_string($key)) {
                        continue;
                    }

                    $records[] = [
                        'group' => $group,
                        'key' => $key,
                    ];
                }

                return $records;
            })
            ->sortBy([
                ['group', 'asc'],
                ['key', 'asc'],
            ])
            ->values();
    }

    /**
     * @return Collection<int, array{group: string, key: string, value: mixed, type: string}>
     */
    public function listOrdered(): Collection
    {
        return collect($this->items)
            ->flatMap(function (array $keys, string $group): array {
                $records = [];

                foreach ($keys as $key => $value) {
                    if (! is_string($key)) {
                        continue;
                    }

                    $path = $group . '.' . $key;
                    $records[] = [
                        'group' => $group,
                        'key' => $key,
                        'value' => $value,
                        'type' => $this->types[$path] ?? $this->inferType($value),
                    ];
                }

                return $records;
            })
            ->sortBy([
                ['group', 'asc'],
                ['key', 'asc'],
            ])
            ->values();
    }

    public function group(string $group): array
    {
        return $this->items[$group] ?? [];
    }

    public function all(): array
    {
        return $this->items;
    }

    public function refresh(): void
    {
        // No-op for in-memory fake.
    }

    public function ensureIndexes(): string
    {
        return 'config_group_key_unique';
    }

    protected function parsePath(string $path): ConfigPath
    {
        return ConfigPath::fromString($path);
    }

    protected function normalizeValue(mixed $value, ConfigType $type): mixed
    {
        return match ($type) {
            ConfigType::String, ConfigType::Encrypted => is_scalar($value) || $value === null ? (string) $value : '',
            ConfigType::Int => is_numeric($value) ? (int) $value : 0,
            ConfigType::Float => is_numeric($value) ? (float) $value : 0.0,
            ConfigType::Bool => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            ConfigType::Array, ConfigType::Json => $this->normalizeArrayValue($value),
            ConfigType::Null => null,
        };
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

        return $this->normalizeArrayMap($decoded);
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

    protected function inferType(mixed $value): string
    {
        return ConfigType::infer($value)->value;
    }
}
