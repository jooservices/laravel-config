<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Console\Commands;

use InvalidArgumentException;
use JOOservices\LaravelConfig\Contracts\ConfigStore;

class ForgetConfigCommand extends ConfigCommand
{
    protected $signature = 'config-store:forget {path}';

    protected $description = 'Forget a config-store value by path.';

    public function handle(ConfigStore $configService): int
    {
        try {
            $deleted = $configService->forget($this->pathArgument());
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
