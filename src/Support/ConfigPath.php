<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Support;

use InvalidArgumentException;

final class ConfigPath
{
    public function __construct(
        public readonly string $group,
        public readonly string $key,
    ) {
    }

    /**
     * Parse `group.key` paths. The first segment is the group; the remainder
     * (joined with dots) is the key, so nested keys like `mail.smtp.host` work.
     */
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

        $parts = array_map(trim(...), explode('.', $normalizedPath));

        if (count($parts) < 2 || in_array('', $parts, true)) {
            throw new InvalidArgumentException("Invalid config path: {$path}. Must be group.key");
        }

        $group = array_shift($parts);
        $key = implode('.', $parts);

        return new self((string) $group, $key);
    }
}
