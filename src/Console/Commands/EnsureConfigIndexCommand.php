<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Console\Commands;

use Illuminate\Console\Command;
use JOOservices\LaravelConfig\Services\ConfigService;

class EnsureConfigIndexCommand extends Command
{
    protected $signature = 'config-store:ensure-index';

    protected $description = 'Ensure the unique MongoDB index exists for config group/key pairs.';

    public function handle(ConfigService $configService): int
    {
        try {
            $indexName = $configService->ensureIndexes();
        } catch (\Throwable $exception) {
            $this->error('Failed to ensure MongoDB index: '.$exception->getMessage());

            return self::FAILURE;
        }

        if ($indexName === '') {
            $this->error('Failed to ensure MongoDB index.');

            return self::FAILURE;
        }

        $this->info("Ensured MongoDB index [{$indexName}].");

        return self::SUCCESS;
    }
}
