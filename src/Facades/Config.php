<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Facades;

use Illuminate\Support\Facades\Facade;
use JOOservices\LaravelConfig\Contracts\ConfigStore;
use JOOservices\LaravelConfig\Services\ConfigService;
use JOOservices\LaravelConfig\Testing\FakeConfigStore;

/**
 * Prefer importing this facade FQCN. Do not register it as Laravel's `Config` alias.
 *
 * @method static mixed get(string $path, mixed $default = null)
 * @method static string|null getString(string $path, ?string $default = null)
 * @method static int|null getInt(string $path, ?int $default = null)
 * @method static float|null getFloat(string $path, ?float $default = null)
 * @method static bool|null getBool(string $path, ?bool $default = null)
 * @method static array<string, mixed>|null getArray(string $path, array<string, mixed>|null $default = null)
 * @method static mixed fresh(string $path, mixed $default = null)
 * @method static void set(string $path, mixed $value, ?string $type = null)
 * @method static void setMany(array<string, array{value: mixed, type?: string|null}|mixed> $entries)
 * @method static mixed remember(string $path, mixed $default, ?string $type = null)
 * @method static bool has(string $path)
 * @method static bool forget(string $path)
 * @method static int forgetMany(array<int, string> $paths)
 * @method static int clear()
 * @method static \Illuminate\Support\Collection<int, array{
 *     group: string,
 *     key: string
 * }> listPaths()
 * @method static \Illuminate\Support\Collection<int, array{
 *     group: string,
 *     key: string,
 *     value: mixed,
 *     type: string
 * }> listOrdered()
 * @method static array<string, mixed> group(string $group)
 * @method static array<string, array<string, mixed>> all()
 * @method static void refresh()
 * @method static string ensureIndexes()
 *
 * @see ConfigService
 */
class Config extends Facade
{
    /**
     * @param  array<string, array<string, mixed>>  $seed
     */
    public static function fake(array $seed = []): FakeConfigStore
    {
        static::swap($fake = new FakeConfigStore($seed));

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return ConfigStore::class;
    }
}
