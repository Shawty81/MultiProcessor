<?php

namespace MultiProcessor\Tests;

use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\Iterator\IteratorInterface;
use MultiProcessor\Settings;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SettingsTest extends TestCase
{
    /**
     * @test
     */
    public function itValidatesMissingIterator(): void
    {
        $settings = (new Settings())
            ->setChildProcessor($this->createMock(ChildProcessorInterface::class))
        ;

        $this->expectException(RuntimeException::class);

        $settings->validate();
    }

    /**
     * @test
     */
    public function itValidatesMissingChildProcessor(): void
    {
        $settings = (new Settings())
            ->setIterator($this->createMock(IteratorInterface::class))
        ;

        $this->expectException(RuntimeException::class);

        $settings->validate();
    }
}
