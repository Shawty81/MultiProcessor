<?php

namespace MultiProcessor\Tests;

use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\Exception\ExceptionInterface;
use MultiProcessor\Exception\InvalidSettingsException;
use MultiProcessor\Iterator\IteratorInterface;
use MultiProcessor\Settings;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    #[Test]
    public function itValidatesChunkSize(): void
    {
        $this->expectException(InvalidSettingsException::class);

        $this->settings(chunkSize: 0);
    }

    #[Test]
    public function itValidatesMaxChildren(): void
    {
        $this->expectException(InvalidSettingsException::class);

        $this->settings(maxChildren: 0);
    }

    #[Test]
    public function itValidatesMaxRetries(): void
    {
        $this->expectException(InvalidSettingsException::class);

        $this->settings(maxRetries: -1);
    }

    #[Test]
    public function itAcceptsSettingsThatAreWithinBounds(): void
    {
        $settings = $this->settings(maxChildren: 1, chunkSize: 1, maxRetries: 0);

        $this->assertSame(1, $settings->chunkSize);
        $this->assertSame(1, $settings->maxChildren);
        $this->assertSame(0, $settings->maxRetries);
    }

    #[Test]
    public function itDefaultsEverythingButTheIteratorAndTheChildProcessor(): void
    {
        $settings = $this->settings();

        $this->assertNull($settings->logger);
        $this->assertSame(1, $settings->maxChildren);
        $this->assertSame(10, $settings->chunkSize);
        $this->assertTrue($settings->retryOnFatal);
        $this->assertSame(1, $settings->maxRetries);
        $this->assertFalse($settings->exitOnFatal);
    }

    #[Test]
    public function itThrowsSomethingEveryConsumerCanCatchWithOneType(): void
    {
        $this->expectException(ExceptionInterface::class);

        $this->settings(chunkSize: 0);
    }

    private function settings(int $maxChildren = 1, int $chunkSize = 10, int $maxRetries = 1): Settings
    {
        return new Settings(
            iterator: $this->createStub(IteratorInterface::class),
            childProcessor: $this->createStub(ChildProcessorInterface::class),
            maxChildren: $maxChildren,
            chunkSize: $chunkSize,
            maxRetries: $maxRetries,
        );
    }
}
