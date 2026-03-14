<?php

declare(strict_types=1);

namespace JooServices\LaravelConfig\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $group
 * @property string $key
 * @property mixed $value
 * @property string $type
 */
class Config extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'configs';

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
    ];

    public function getCollectionName(): string
    {
        return $this->collection;
    }
}
