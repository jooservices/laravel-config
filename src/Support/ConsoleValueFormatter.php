<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Support;

use JsonException;
use Throwable;

final class ConsoleValueFormatter
{
    public const REDACTED = '[redacted]';

    public static function format(mixed $value): string
    {
        if (is_array($value)) {
            return (string) json_encode($value, JSON_THROW_ON_ERROR);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_object($value)) {
            return self::formatObject($value);
        }

        if (is_resource($value)) {
            return 'resource(' . get_resource_type($value) . ')';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return var_export($value, true);
    }

    public static function formatObject(object $value): string
    {
        try {
            return (string) json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            try {
                return var_export($value, true);
            } catch (Throwable) {
                return $value::class . ' object';
            }
        }
    }

    public static function displayValue(mixed $value, string $type, bool $revealSecrets): mixed
    {
        $configType = ConfigType::tryFrom($type);

        if ($configType !== null && $configType->isSensitive() && ! $revealSecrets) {
            return self::REDACTED;
        }

        return $value;
    }
}
