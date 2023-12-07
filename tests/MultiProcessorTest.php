<?php

namespace MultiProcessor\Tests;

use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\Iterator\IteratorInterface;
use MultiProcessor\MultiProcessor;
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

        $childProcessor = $this->createMock(ChildProcessorInterface::class);
        $childProcessor
            ->expects($this->once())
            ->method('init');

        $mp = new MultiProcessor($iterator, $childProcessor, 10);

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

        $childProcessor = $this->createMock(ChildProcessorInterface::class);
        $childProcessor
            ->expects($this->once())
            ->method('finish');

        $mp = new MultiProcessor($iterator, $childProcessor, 10);

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
            ->willReturnOnConsecutiveCalls(['1'], ['2'], []);

        $childProcessor = $this->createMock(ChildProcessorInterface::class);

        $mp = new MultiProcessor($iterator, $childProcessor, 10);

        $mp->run();
    }

}