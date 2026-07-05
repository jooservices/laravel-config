<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Tests;

use JOOservices\LaravelConfig\Console\Commands\GetConfigCommand;
use ReflectionMethod;
use stdClass;

class GetConfigCommandTest extends TestCase
{
    public function test_format_object_falls_back_when_json_encoding_fails(): void
    {
        $command = $this->app->make(GetConfigCommand::class);
        $object = new stdClass();
        $object->self = $object;

        $method = new ReflectionMethod(GetConfigCommand::class, 'formatObject');
        $formatted = $method->invoke($command, $object);
        $this->assertIsString($formatted);

        $this->assertStringContainsString('stdClass', $formatted);
    }

    public function test_format_value_falls_back_for_non_scalar_non_object_values(): void
    {
        $command = $this->app->make(GetConfigCommand::class);
        $method = new ReflectionMethod(GetConfigCommand::class, 'formatValue');
        $resource = fopen('php://memory', 'rb');
        $this->assertIsResource($resource);

        try {
            $formatted = $method->invoke($command, $resource);
            $this->assertIsString($formatted);
            $this->assertStringContainsString('resource', $formatted);
        } finally {
            fclose($resource);
        }
    }
}
