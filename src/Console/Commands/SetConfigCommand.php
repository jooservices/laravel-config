<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Console\Commands;

use InvalidArgumentException;
use JOOservices\LaravelConfig\Contracts\ConfigStore;

class SetConfigCommand extends ConfigCommand
{
    protected $signature = 'config-store:set {path} {value} {--type=}';

    protected $description = 'Set a config-store value by path.';

    public function handle(ConfigStore $configService): int
    {
        try {
            $configService->set(
                $this->pathArgument(),
                $this->argument('value'),
                $this->typeOption(),
            );
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Config value stored.');

        return self::SUCCESS;
    }
}
