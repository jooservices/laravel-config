<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Console\Commands;

use Illuminate\Console\Command;
use JOOservices\LaravelConfig\Contracts\ConfigStore;
use JOOservices\LaravelConfig\Support\ConfigType;
use JsonException;

class ExportConfigCommand extends Command
{
    protected $signature = 'config-store:export {file?} {--group=} {--reveal-secrets}';

    protected $description = 'Export config-store values to typed JSON (__type / __value).';

    public function handle(ConfigStore $configService): int
    {
        $revealSecrets = (bool) $this->option('reveal-secrets');
        $groupFilter = $this->groupFilter();

        [$payload, $skippedSecrets] = $this->buildExportPayload($configService, $groupFilter, $revealSecrets);

        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        } catch (JsonException $exception) {
            $this->error('Unable to encode config values as JSON: ' . $exception->getMessage());

            return self::FAILURE;
        }

        if ($skippedSecrets > 0) {
            $this->warn(sprintf(
                'Skipped %d encrypted value(s). Re-run with --reveal-secrets to include plaintext secrets.',
                $skippedSecrets,
            ));
        }

        return $this->writeExport($json);
    }

    private function groupFilter(): ?string
    {
        $group = $this->option('group');

        return is_string($group) && $group !== '' ? $group : null;
    }

    /**
     * @return array{0: array<string, array<string, array{__type: string, __value: mixed}>>, 1: int}
     */
    private function buildExportPayload(ConfigStore $configService, ?string $groupFilter, bool $revealSecrets): array
    {
        $payload = [];
        $skippedSecrets = 0;

        foreach ($configService->listOrdered() as $row) {
            if ($groupFilter !== null && $row['group'] !== $groupFilter) {
                continue;
            }

            $type = $row['type'] === '' ? ConfigType::String->value : $row['type'];
            $configType = ConfigType::tryFrom($type);

            if ($configType !== null && $configType->isSensitive() && ! $revealSecrets) {
                $skippedSecrets++;

                continue;
            }

            $payload[$row['group']][$row['key']] = [
                '__type' => $type,
                '__value' => $row['value'],
            ];
        }

        return [$payload, $skippedSecrets];
    }

    private function writeExport(string $json): int
    {
        $file = $this->argument('file');

        if (! is_string($file) || $file === '') {
            $this->line($json);

            return self::SUCCESS;
        }

        if (is_dir($file) || file_put_contents($file, $json) === false) {
            $this->error('Unable to write export file.');

            return self::FAILURE;
        }

        $this->info('Config values exported to ' . $file);

        return self::SUCCESS;
    }
}
