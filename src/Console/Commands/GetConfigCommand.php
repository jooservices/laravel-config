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
        $formatted = (string) $value;

        if (is_array($value)) {
            $formatted = (string) json_encode($value, JSON_THROW_ON_ERROR);
        }
        if (is_bool($value)) {
            $formatted = $value ? 'true' : 'false';
        }
        if ($value === null) {
            $formatted = 'null';
        }

        return $formatted;
    }
}
