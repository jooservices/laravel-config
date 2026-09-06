<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Support;

use InvalidArgumentException;

enum ConfigType: string
{
    case String = 'string';
    case Int = 'int';
    case Float = 'float';
    case Bool = 'bool';
    case Array = 'array';
    case Json = 'json';
    case Null = 'null';
    case Encrypted = 'encrypted';

    public static function parse(string $type): self
    {
        return self::tryFrom($type) ?? throw new InvalidArgumentException(
            sprintf('Unsupported config type [%s]. Supported types: %s.', $type, self::supportedList()),
        );
    }

    public static function infer(mixed $value): self
    {
        return match (true) {
            $value === null => self::Null,
            is_bool($value) => self::Bool,
            is_int($value) => self::Int,
            is_float($value) => self::Float,
            is_array($value) => self::Array,
            default => self::String,
        };
    }

    public function isSensitive(): bool
    {
        return $this === self::Encrypted;
    }

    /**
     * @return list<string>
     */
    public static function supportedValues(): array
    {
        return array_map(static fn(self $type): string => $type->value, self::cases());
    }

    public static function supportedList(): string
    {
        return implode(', ', self::supportedValues());
    }
}
