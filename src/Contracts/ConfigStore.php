<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Contracts;

use Illuminate\Support\Collection;

interface ConfigStore
{
    public function get(string $path, mixed $default = null): mixed;

    public function getString(string $path, ?string $default = null): ?string;

    public function getInt(string $path, ?int $default = null): ?int;

    public function getFloat(string $path, ?float $default = null): ?float;

    public function getBool(string $path, ?bool $default = null): ?bool;

    /**
     * @param  array<string, mixed>|null  $default
     * @return array<string, mixed>|null
     */
    public function getArray(string $path, ?array $default = null): ?array;

    public function fresh(string $path, mixed $default = null): mixed;

    public function set(string $path, mixed $value, ?string $type = null): void;

    /**
     * Persist many paths in one write cycle (single cache version bump).
     *
     * @param  array<string, array{value: mixed, type?: string|null}|mixed>  $entries
     *         Map of path => value, or path => ['value' => mixed, 'type' => ?string]
     */
    public function setMany(array $entries): void;

    public function remember(string $path, mixed $default, ?string $type = null): mixed;

    public function has(string $path): bool;

    public function forget(string $path): bool;

    /**
     * @return Collection<int, array{group: string, key: string}>
     */
    public function listPaths(): Collection;

    /**
     * @return Collection<int, array{group: string, key: string, value: mixed, type: string}>
     */
    public function listOrdered(): Collection;

    /**
     * @return array<string, mixed>
     */
    public function group(string $group): array;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array;

    public function refresh(): void;

    public function ensureIndexes(): string;
}
