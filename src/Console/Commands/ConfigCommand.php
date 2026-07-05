<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;

abstract class ConfigCommand extends Command
{
    protected function pathArgument(): string
    {
        $path = $this->argument('path');

        if (! is_string($path)) {
            throw new InvalidArgumentException('Path must be a string.');
        }

        return $path;
    }

    protected function fileArgument(): string
    {
        $file = $this->argument('file');

        if (! is_string($file)) {
            throw new InvalidArgumentException('File must be a string.');
        }

        return $file;
    }

    protected function typeOption(): ?string
    {
        $type = $this->option('type');

        if (! is_string($type) || $type === '') {
            return null;
        }

        return $type;
    }
}
