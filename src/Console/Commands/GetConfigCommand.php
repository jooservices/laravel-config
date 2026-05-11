<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Console\Commands;

use Illuminate\Console\Command;
use JOOservices\LaravelConfig\Services\ConfigService;

class GetConfigCommand extends Command
{
    protected $signature = 'config-store:get {path} {--default=}';

    protected $description = 'Get a config-store value by path.';

    public function handle(ConfigService $configService): int
    {
        $value = $configService->get(
            (string) $this->argument('path'),
            $this->option('default')
        );

        $this->line($this->formatValue($value));

        return self::SUCCESS;
    }

    protected function formatValue(mixed $value): string
    {
        if (is_array($value)) {
            return (string) json_encode($value, JSON_THROW_ON_ERROR);
        }

        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            default => (string) $value,
        };
    }
}
