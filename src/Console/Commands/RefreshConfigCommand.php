<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Console\Commands;

use Illuminate\Console\Command;
use JOOservices\LaravelConfig\Services\ConfigService;

class RefreshConfigCommand extends Command
{
    protected $signature = 'config-store:refresh';

    protected $description = 'Refresh the in-memory and cache state from MongoDB.';

    public function handle(ConfigService $configService): int
    {
        $configService->refresh();

        $this->info('Config store refreshed.');

        return self::SUCCESS;
    }
}
