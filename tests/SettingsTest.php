<?php

namespace MultiProcessor\Tests;

use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\Iterator\IteratorInterface;
use MultiProcessor\Settings;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SettingsTest extends TestCase
{
    #[Test]
    public function itValidatesMissingIterator(): void
    {
        $settings = new Settings()
            ->setChildProcessor($this->createMock(ChildProcessorInterface::class))
        ;

        $this->expectException(RuntimeException::class);

        $settings->validate();
    }

    #[Test]
    public function itValidatesMissingChildProcessor(): void
    {
        $settings = new Settings()
            ->setIterator($this->createMock(IteratorInterface::class))
        ;

        $this->expectException(RuntimeException::class);

        $settings->validate();
    }
}
