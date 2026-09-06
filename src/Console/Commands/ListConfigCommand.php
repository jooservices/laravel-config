<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Console\Commands;

use Illuminate\Console\Command;
use JOOservices\LaravelConfig\Contracts\ConfigStore;
use JOOservices\LaravelConfig\Support\ConsoleValueFormatter;
use JsonException;

class ListConfigCommand extends Command
{
    protected $signature = 'config-store:list {group?} {--json} {--with-types} {--reveal-secrets}';

    protected $description = 'List config-store values, optionally filtered by group.';

    public function handle(ConfigStore $configService): int
    {
        $groupFilter = $this->argument('group');
        $groupFilter = is_string($groupFilter) && $groupFilter !== '' ? $groupFilter : null;
        $withTypes = (bool) $this->option('with-types');
        $revealSecrets = (bool) $this->option('reveal-secrets');
        $asJson = (bool) $this->option('json');

        $rows = $configService->listOrdered()
            ->when(
                $groupFilter !== null,
                static fn($collection) => $collection->filter(
                    static fn(array $row): bool => $row['group'] === $groupFilter,
                )->values(),
            );

        if ($rows->isEmpty()) {
            if ($asJson) {
                $this->line($withTypes ? '[]' : '{}');

                return self::SUCCESS;
            }

            $this->info('No config values found.');

            return self::SUCCESS;
        }

        if ($asJson) {
            /** @var list<array{group: string, key: string, value: mixed, type: string}> $list */
            $list = array_values($rows->all());

            return $this->writeJson($list, $withTypes, $revealSecrets);
        }

        foreach ($rows as $row) {
            $display = ConsoleValueFormatter::displayValue($row['value'], $row['type'], $revealSecrets);
            $formatted = ConsoleValueFormatter::format($display);

            if ($withTypes) {
                $this->line(sprintf(
                    '%s.%s (%s) = %s',
                    $row['group'],
                    $row['key'],
                    $row['type'],
                    $formatted,
                ));

                continue;
            }

            $this->line(sprintf('%s.%s = %s', $row['group'], $row['key'], $formatted));
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<array{group: string, key: string, value: mixed, type: string}>  $rows
     */
    private function writeJson(array $rows, bool $withTypes, bool $revealSecrets): int
    {
        if ($withTypes) {
            $payload = array_map(
                static function (array $row) use ($revealSecrets): array {
                    return [
                        'group' => $row['group'],
                        'key' => $row['key'],
                        'type' => $row['type'],
                        'value' => ConsoleValueFormatter::displayValue(
                            $row['value'],
                            $row['type'],
                            $revealSecrets,
                        ),
                    ];
                },
                $rows,
            );
        } else {
            $payload = [];

            foreach ($rows as $row) {
                $payload[$row['group']][$row['key']] = ConsoleValueFormatter::displayValue(
                    $row['value'],
                    $row['type'],
                    $revealSecrets,
                );
            }
        }

        try {
            $this->line((string) json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        } catch (JsonException $exception) {
            $this->error('Unable to encode config values as JSON: ' . $exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
