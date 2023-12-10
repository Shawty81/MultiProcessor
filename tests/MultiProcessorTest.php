<?php

namespace MultiProcessor\Tests;

use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\Iterator\IteratorInterface;
use MultiProcessor\MultiProcessor;
use MultiProcessor\Queue\Chunk;
use MultiProcessor\Settings;
use PHPUnit\Framework\TestCase;

class MultiProcessorTest extends TestCase
{
    /**
     * @test
     */
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

        $settings = (new Settings())
            ->setIterator($iterator)
            ->setChildProcessor($childProcessor)
            ->setMaxChildren(10)
        ;

        $mp = new MultiProcessor($settings);

        $mp->run();
    }

    /**
     * @test
     */
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

        $settings = (new Settings())
            ->setIterator($iterator)
            ->setChildProcessor($childProcessor)
            ->setMaxChildren(10)
        ;

        $mp = new MultiProcessor($settings);

        $mp->run();
    }

    /**
     * @test
     */
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

        $childProcessor = $this->createMock(ChildProcessorInterface::class);

        $settings = (new Settings())
            ->setIterator($iterator)
            ->setChildProcessor($childProcessor)
            ->setMaxChildren(10)
        ;

        $mp = new MultiProcessor($settings);

        $mp->run();
    }

}
