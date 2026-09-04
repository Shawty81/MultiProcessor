<?php

namespace MultiProcessor\Tests;

use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\Iterator\IteratorInterface;
use MultiProcessor\MultiProcessor;
use MultiProcessor\Queue\Chunk;
use MultiProcessor\Settings;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MultiProcessorTest extends TestCase
{
    #[Test]
    public function itInitializesCorrectly(): void
    {
        $iterator = $this->createMock(IteratorInterface::class);
        $iterator
            ->expects($this->once())
            ->method('init');
        $iterator
            ->expects($this->once())
            ->method('getChunk')
            ->willReturn(new Chunk([]));

        $childProcessor = $this->createMock(ChildProcessorInterface::class);
        $childProcessor
            ->expects($this->once())
            ->method('init');

        $settings = new Settings(
            iterator: $iterator,
            childProcessor: $childProcessor,
            maxChildren: 10,
        );

        $mp = new MultiProcessor($settings);

        $mp->run();
    }

    #[Test]
    public function itFinishesCorrectly(): void
    {
        $iterator = $this->createMock(IteratorInterface::class);
        $iterator
            ->expects($this->once())
            ->method('finish');
        $iterator
            ->expects($this->once())
            ->method('getChunk')
            ->willReturn(new Chunk([]));

        $childProcessor = $this->createMock(ChildProcessorInterface::class);
        $childProcessor
            ->expects($this->once())
            ->method('finish');

        $settings = new Settings(
            iterator: $iterator,
            childProcessor: $childProcessor,
            maxChildren: 10,
        );

        $mp = new MultiProcessor($settings);

        $mp->run();
    }

    #[Test]
    public function itGetsChunksAndStopsOnEmptyChunk(): void
    {
        $iterator = $this->createMock(IteratorInterface::class);
        $iterator
            ->expects($this->exactly(3))
            ->method('getChunk')
            ->willReturnOnConsecutiveCalls(
                new Chunk(['1']),
                new Chunk(['2']),
                new Chunk([])
            );

        $childProcessor = $this->createStub(ChildProcessorInterface::class);

        $settings = new Settings(
            iterator: $iterator,
            childProcessor: $childProcessor,
            maxChildren: 10,
        );

        $mp = new MultiProcessor($settings);

        $mp->run();
    }

}
