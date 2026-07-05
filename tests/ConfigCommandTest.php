<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Tests;

use Illuminate\Console\Command;
use InvalidArgumentException;
use JOOservices\LaravelConfig\Console\Commands\ImportConfigCommand;
use JOOservices\LaravelConfig\Console\Commands\SetConfigCommand;
use ReflectionMethod;
use Symfony\Component\Console\Input\ArrayInput;

class ConfigCommandTest extends TestCase
{
    public function test_path_argument_rejects_non_string_values(): void
    {
        $command = $this->app->make(SetConfigCommand::class);
        $this->bindCommandInput($command, ['path' => 123, 'value' => 'ignored']);

        $method = new ReflectionMethod(SetConfigCommand::class, 'pathArgument');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path must be a string.');
        $method->invoke($command);
    }

    public function test_file_argument_rejects_non_string_values(): void
    {
        $command = $this->app->make(ImportConfigCommand::class);
        $this->bindCommandInput($command, ['file' => []]);

        $method = new ReflectionMethod(ImportConfigCommand::class, 'fileArgument');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File must be a string.');
        $method->invoke($command);
    }

    public function test_file_argument_accepts_string_values(): void
    {
        $command = $this->app->make(ImportConfigCommand::class);
        $this->bindCommandInput($command, ['file' => '/tmp/example.json']);

        $method = new ReflectionMethod(ImportConfigCommand::class, 'fileArgument');

        $this->assertSame('/tmp/example.json', $method->invoke($command));
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function bindCommandInput(Command $command, array $arguments): void
    {
        $input = new ArrayInput($arguments, $command->getDefinition());

        $reflection = new \ReflectionObject($command);
        $inputProperty = $reflection->getProperty('input');
        $inputProperty->setAccessible(true);
        $inputProperty->setValue($command, $input);
    }
}
