<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Console\Commands;

use JOOservices\LaravelConfig\Contracts\ConfigStore;
use JOOservices\LaravelConfig\Support\ConfigPath;
use JOOservices\LaravelConfig\Support\ConsoleValueFormatter;

class GetConfigCommand extends ConfigCommand
{
    protected $signature = 'config-store:get {path} {--default=} {--reveal-secrets}';

    protected $description = 'Get a config-store value by path.';

    public function handle(ConfigStore $configService): int
    {
        $path = $this->pathArgument();
        $default = $this->option('default');
        $revealSecrets = (bool) $this->option('reveal-secrets');

        if (! $configService->has($path)) {
            $this->line(ConsoleValueFormatter::format($default));

            return self::SUCCESS;
        }

        $configPath = ConfigPath::fromString($path);
        $row = $configService->listOrdered()->first(
            static fn(array $item): bool => $item['group'] === $configPath->group
                && $item['key'] === $configPath->key,
        );

        $type = is_array($row) ? (string) ($row['type'] ?? 'string') : 'string';
        $value = $configService->get($path);
        $display = ConsoleValueFormatter::displayValue($value, $type, $revealSecrets);

        $this->line(ConsoleValueFormatter::format($display));

        return self::SUCCESS;
    }
}
