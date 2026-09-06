<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConfigChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $path,
        public readonly mixed $value,
        public readonly string $type,
    ) {
    }

    public function isRedacted(): bool
    {
        return $this->type === 'encrypted';
    }
}
