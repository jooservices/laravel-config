<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * @internal Direct model writes bypass normalization and cache coherence.
 *
 * @property string $group
 * @property string $key
 * @property mixed $value
 * @property string $type
 */
class Config extends Model
{
    protected $connection = 'mongodb';

    protected string $collection = 'configs';

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
    ];
}
