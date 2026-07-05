<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Console\Commands;

use Illuminate\Console\Command;
use JOOservices\LaravelConfig\Contracts\ConfigStore;
use JsonException;

class ExportConfigCommand extends Command
{
    protected $signature = 'config-store:export {file?} {--group=}';

    protected $description = 'Export config-store values to JSON.';

    public function handle(ConfigStore $configService): int
    {
        $group = $this->option('group');
        $items = is_string($group) && $group !== ''
            ? [$group => $configService->group($group)]
            : $configService->all();

        try {
            $json = json_encode($items, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        } catch (JsonException $exception) {
            $this->error('Unable to encode config values as JSON: '.$exception->getMessage());

            return self::FAILURE;
        }

        $file = $this->argument('file');

        if (is_string($file) && $file !== '') {
            if (is_dir($file)) {
                $this->error('Unable to write export file.');

                return self::FAILURE;
            }

            $written = file_put_contents($file, $json);
            if ($written === false) {
                $this->error('Unable to write export file.');

                return self::FAILURE;
            }

            $this->info('Config values exported to '.$file);

            return self::SUCCESS;
        }

        $this->line($json);

        return self::SUCCESS;
    }
}
