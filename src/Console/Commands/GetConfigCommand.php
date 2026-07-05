<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Console\Commands;

use JOOservices\LaravelConfig\Contracts\ConfigStore;
use JsonException;

class GetConfigCommand extends ConfigCommand
{
    protected $signature = 'config-store:get {path} {--default=}';

    protected $description = 'Get a config-store value by path.';

    public function handle(ConfigStore $configService): int
    {
        $default = $this->option('default');

        $value = $configService->get(
            $this->pathArgument(),
            $default
        );

        $this->line($this->formatValue($value));

        return self::SUCCESS;
    }

    protected function formatValue(mixed $value): string
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
            return $this->formatObject($value);
        }

        if (is_resource($value)) {
            return 'resource('.get_resource_type($value).')';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return var_export($value, true);
    }

    protected function formatObject(object $value): string
    {
        try {
            return (string) json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            try {
                return var_export($value, true);
            } catch (\Throwable) {
                return $value::class.' object';
            }
        }
    }
}
