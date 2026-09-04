<?php

namespace MultiProcessor\Tests;

use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\Exception\InvalidSettingsException;
use MultiProcessor\Iterator\IteratorInterface;
use MultiProcessor\Settings;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    #[Test]
    public function itValidatesMissingIterator(): void
    {
        $settings = new Settings()
            ->setChildProcessor($this->createStub(ChildProcessorInterface::class))
        ;

        $this->expectException(InvalidSettingsException::class);

        $settings->validate();
    }

    #[Test]
    public function itValidatesMissingChildProcessor(): void
    {
        $settings = new Settings()
            ->setIterator($this->createStub(IteratorInterface::class))
        ;

        $this->expectException(InvalidSettingsException::class);

        $settings->validate();
    }

    #[Test]
    public function itValidatesChunkSize(): void
    {
        $settings = $this->completeSettings()->setChunkSize(0);

        $this->expectException(InvalidSettingsException::class);

        $settings->validate();
    }

    #[Test]
    public function itValidatesMaxChildren(): void
    {
        $settings = $this->completeSettings()->setMaxChildren(0);

        $this->expectException(InvalidSettingsException::class);

        $settings->validate();
    }

    #[Test]
    public function itValidatesMaxRetries(): void
    {
        $settings = $this->completeSettings()->setMaxRetries(-1);

        $this->expectException(InvalidSettingsException::class);

        $settings->validate();
    }

    #[Test]
    public function itAcceptsSettingsThatAreWithinBounds(): void
    {
        $settings = $this->completeSettings()
            ->setChunkSize(1)
            ->setMaxChildren(1)
            ->setMaxRetries(0)
        ;

        $settings->validate();

        $this->assertSame(1, $settings->getChunkSize());
        $this->assertSame(1, $settings->getMaxChildren());
        $this->assertSame(0, $settings->getMaxRetries());
    }

    private function completeSettings(): Settings
    {
        return new Settings()
            ->setIterator($this->createStub(IteratorInterface::class))
            ->setChildProcessor($this->createStub(ChildProcessorInterface::class))
        ;
    }
}
