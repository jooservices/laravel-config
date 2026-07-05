<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Tests\Benchmark;

use JOOservices\LaravelConfig\Support\ConfigType;
use RuntimeException;

final class ConfigTypeBench
{
    private mixed $sampleValue;

    public function __construct()
    {
        $this->sampleValue = ['group' => ['key' => 'value', 'nested' => ['a' => 1, 'b' => 2]]];
    }

    /**
     * @Revs(5000)
     *
     * @Iterations(5)
     */
    public function benchParseKnownType(): void
    {
        ConfigType::parse('string');
    }

    /**
     * @Revs(5000)
     *
     * @Iterations(5)
     */
    public function benchInferArrayValue(): void
    {
        $inferred = ConfigType::infer($this->sampleValue);

        if ($inferred !== ConfigType::Array) {
            throw new RuntimeException('Expected array config type for benchmark fixture.');
        }
    }

    /**
     * @Revs(2000)
     *
     * @Iterations(5)
     */
    public function benchSupportedList(): void
    {
        ConfigType::supportedList();
    }
}
