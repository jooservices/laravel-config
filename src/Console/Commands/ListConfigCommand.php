<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Console\Commands;

use Illuminate\Console\Command;
use JOOservices\LaravelConfig\Contracts\ConfigStore;
use JsonException;

class ListConfigCommand extends Command
{
    protected $signature = 'config-store:list {group?} {--json}';

    protected $description = 'List config-store values, optionally filtered by group.';

    public function handle(ConfigStore $configService): int
    {
        $group = $this->argument('group');

        if (is_string($group) && $group !== '') {
            $groupItems = $configService->group($group);
            $items = $groupItems === [] ? [] : [$group => $groupItems];
        } else {
            $items = $configService->all();
        }

        if ((bool) $this->option('json')) {
            try {
                $this->line((string) json_encode($items, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            } catch (JsonException $exception) {
                $this->error('Unable to encode config values as JSON: '.$exception->getMessage());

                return self::FAILURE;
            }

            return self::SUCCESS;
        }

        if ($items === []) {
            $this->info('No config values found.');

            return self::SUCCESS;
        }

        foreach ($items as $groupName => $keys) {
            foreach ($keys as $key => $value) {
                $this->line(sprintf('%s.%s = %s', $groupName, $key, $this->formatValue($value)));
            }
        }

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

        if (is_scalar($value)) {
            return (string) $value;
        }

        return var_export($value, true);
    }
}
