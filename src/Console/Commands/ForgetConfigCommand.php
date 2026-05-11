<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use JOOservices\LaravelConfig\Services\ConfigService;

class ForgetConfigCommand extends Command
{
    protected $signature = 'config-store:forget {path}';

    protected $description = 'Forget a config-store value by path.';

    public function handle(ConfigService $configService): int
    {
        try {
            $deleted = $configService->forget((string) $this->argument('path'));
        } catch (InvalidArgumentException $exception) {
            $this->warn($exception->getMessage());

            return self::FAILURE;
        }

        if (! $deleted) {
            $this->warn('Config value not found.');

            return self::FAILURE;
        }

        $this->info('Config value removed.');

        return self::SUCCESS;
    }
}
