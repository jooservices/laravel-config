<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Testing;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use JOOservices\LaravelConfig\Contracts\ConfigStore;
use JOOservices\LaravelConfig\Support\ConfigPath;
use JOOservices\LaravelConfig\Support\ConfigType;

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

                $this->items[$group][$key] = $value;
                $this->types[$group . '.' . $key] = ConfigType::infer($value)->value;
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
        $value = $this->get($path, $default);

        return is_string($value) ? $value : $default;
    }

    public function getInt(string $path, ?int $default = null): ?int
    {
        $value = $this->get($path, $default);

        return is_int($value) ? $value : $default;
    }

    public function getFloat(string $path, ?float $default = null): ?float
    {
        $value = $this->get($path, $default);

        return is_float($value) ? $value : $default;
    }

    public function getBool(string $path, ?bool $default = null): ?bool
    {
        $value = $this->get($path, $default);

        return is_bool($value) ? $value : $default;
    }

    /**
     * @param  array<string, mixed>|null  $default
     * @return array<string, mixed>|null
     */
    public function getArray(string $path, ?array $default = null): ?array
    {
        $value = $this->get($path, $default);

        if (! is_array($value)) {
            return $default;
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $normalized[$key] = $item;
            }
        }

        return $normalized;
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
            $configType = is_string($type) && $type !== ''
                ? ConfigType::parse($type)
                : ConfigType::infer($value);

            $normalizedPath = $configPath->group . '.' . $configPath->key;
            $this->items[$configPath->group] ??= [];
            $this->items[$configPath->group][$configPath->key] = $this->normalizeValue($value, $configType);
            $this->types[$normalizedPath] = $configType->value;
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
        $configPath = $this->parsePath($path);

        if (
            ! array_key_exists($configPath->group, $this->items)
            || ! array_key_exists($configPath->key, $this->items[$configPath->group])
        ) {
            return false;
        }

        $normalizedPath = $configPath->group . '.' . $configPath->key;
        unset($this->items[$configPath->group][$configPath->key], $this->types[$normalizedPath]);

        if ($this->items[$configPath->group] === []) {
            unset($this->items[$configPath->group]);
        }

        return true;
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
            ConfigType::Array, ConfigType::Json => is_array($value) ? $value : [],
            ConfigType::Null => null,
        };
    }

    protected function inferType(mixed $value): string
    {
        return ConfigType::infer($value)->value;
    }
}
