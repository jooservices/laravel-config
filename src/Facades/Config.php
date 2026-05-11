<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Facades;

use Illuminate\Support\Facades\Facade;
use JOOservices\LaravelConfig\Services\ConfigService;

/**
 * @method static mixed get(string $path, mixed $default = null)
 * @method static mixed fresh(string $path, mixed $default = null)
 * @method static void set(string $path, mixed $value, ?string $type = null)
 * @method static bool has(string $path)
 * @method static bool forget(string $path)
 * @method static array group(string $group)
 * @method static array all()
 * @method static void refresh()
 *
 * @see ConfigService
 */
class Config extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'config-store';
    }
}
