<?php

namespace MultiProcessor\Tests\Log;

use Closure;
use MultiProcessor\Log\CommandLineLogger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Stringable;

class CommandLineLoggerTest extends TestCase
{
    #[Test]
    public function itLogsAMessageContainingAPercentSign(): void
    {
        $output = $this->capture(fn(CommandLineLogger $logger) => $logger->info('50% done'));

        $this->assertStringContainsString('50% done', $output);
    }

    #[Test]
    public function itLogsAContextValueContainingAPercentSign(): void
    {
        $output = $this->capture(
            fn(CommandLineLogger $logger) => $logger->info('Placeholder: {value}', ['value' => '%s'])
        );

        $this->assertStringContainsString('Placeholder: %s', $output);
    }

    #[Test]
    public function itInterpolatesScalarAndStringableValues(): void
    {
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return 'a stringable';
            }
        };

        $output = $this->capture(
            fn(CommandLineLogger $logger) => $logger->info(
                '{pid} / {ratio} / {stringable}',
                ['pid' => 42, 'ratio' => 1.5, 'stringable' => $stringable]
            )
        );

        $this->assertStringContainsString('42 / 1.5 / a stringable', $output);
    }

    #[Test]
    public function itLeavesPlaceholdersItCannotStringifyAlone(): void
    {
        $output = $this->capture(
            fn(CommandLineLogger $logger) => $logger->info('Payload: {payload}', ['payload' => ['a', 'b']])
        );

        $this->assertStringContainsString('Payload: {payload}', $output);
    }

    #[Test]
    public function itPrefixesTheLineWithTheFirstLetterOfTheLevel(): void
    {
        $output = $this->capture(
            fn(CommandLineLogger $logger) => $logger->log(LogLevel::ALERT, 'Something happened')
        );

        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2} \[A]  Something happened$/m', $output);
    }

    private function capture(Closure $closure): string
    {
        $logger = new CommandLineLogger();

        ob_start();

        try {
            $closure($logger);
        } finally {
            $output = (string) ob_get_clean();
        }

        return $output;
    }
}
