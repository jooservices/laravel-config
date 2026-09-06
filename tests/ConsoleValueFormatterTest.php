<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Tests;

use JOOservices\LaravelConfig\Support\ConsoleValueFormatter;
use stdClass;

class ConsoleValueFormatterTest extends TestCase
{
    public function test_format_object_falls_back_when_json_encoding_fails(): void
    {
        $object = new stdClass();
        $object->self = $object;

        $formatted = ConsoleValueFormatter::formatObject($object);

        $this->assertStringContainsString('stdClass', $formatted);
    }

    public function test_format_falls_back_for_resource_values(): void
    {
        $resource = fopen('php://memory', 'rb');
        $this->assertIsResource($resource);

        try {
            $formatted = ConsoleValueFormatter::format($resource);
            $this->assertStringContainsString('resource', $formatted);
        } finally {
            fclose($resource);
        }
    }

    public function test_format_handles_bool_null_and_array(): void
    {
        $this->assertSame('true', ConsoleValueFormatter::format(true));
        $this->assertSame('null', ConsoleValueFormatter::format(null));
        $this->assertSame('{"a":1}', ConsoleValueFormatter::format(['a' => 1]));
    }

    public function test_display_value_redacts_sensitive_types(): void
    {
        $this->assertSame(
            ConsoleValueFormatter::REDACTED,
            ConsoleValueFormatter::displayValue('secret', 'encrypted', false),
        );
        $this->assertSame(
            'secret',
            ConsoleValueFormatter::displayValue('secret', 'encrypted', true),
        );
    }
}
