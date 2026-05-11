<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Console\Commands;

use Illuminate\Console\Command;
use JOOservices\LaravelConfig\Services\ConfigService;

class SetConfigCommand extends Command
{
    protected $signature = 'config-store:set {path} {value} {--type=}';

    protected $description = 'Set a config-store value by path.';

    public function handle(ConfigService $configService): int
    {
        $configService->set(
            (string) $this->argument('path'),
            $this->argument('value'),
            $this->option('type') ?: null
        );

        $this->info('Config value stored.');

        return self::SUCCESS;
    }
}
