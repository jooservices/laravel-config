<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Support;

use InvalidArgumentException;

final class ConfigPath
{
    public function __construct(
        public readonly string $group,
        public readonly string $key,
    ) {}

    public static function fromString(string $path): self
    {
        $normalizedPath = trim($path);

        if (
            $normalizedPath === ''
            || str_starts_with($normalizedPath, '.')
            || str_ends_with($normalizedPath, '.')
            || str_contains($normalizedPath, '..')
        ) {
            throw new InvalidArgumentException("Invalid config path: {$path}. Must be group.key");
        }

        $parts = array_map('trim', explode('.', $normalizedPath));

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new InvalidArgumentException("Invalid config path: {$path}. Must be group.key");
        }

        return new self($parts[0], $parts[1]);
    }
}
