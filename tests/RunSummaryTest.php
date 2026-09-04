<?php

declare(strict_types=1);

namespace MultiProcessor\Tests;

use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\Iterator\IteratorInterface;
use MultiProcessor\MultiProcessor;
use MultiProcessor\Queue\Chunk;
use MultiProcessor\Settings;
use MultiProcessor\Tests\Doubles\RecordingLogger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Covers the summary the parent logs once the run is over.
 */
class RunSummaryTest extends TestCase
{
    #[Test]
    public function itReportsTheElapsedTimeOfAShortRun(): void
    {
        $logger = new RecordingLogger();

        $iterator = $this->createStub(IteratorInterface::class);
        $iterator
            ->method('getChunk')
            ->willReturn(new Chunk([]));

        $settings = new Settings(
            iterator: $iterator,
            childProcessor: $this->createStub(ChildProcessorInterface::class),
            logger: $logger,
        );

        new MultiProcessor($settings)->run();

        $this->assertMatchesRegularExpression(
            '/^0 hours, 0 minutes and \d+ seconds$/',
            (string) $logger->lastValueFor('Total time spent: {time}', 'time')
        );
    }

    /**
     * A run of more than a day used to be reported as the hours left over after the whole
     * days, so 25 hours came out as 1 hour and the day was never mentioned.
     */
    #[Test]
    public function itReportsTheWholeElapsedTimeOfARunThatLastedLongerThanADay(): void
    {
        $logger = new RecordingLogger();

        $settings = new Settings(
            iterator: $this->createStub(IteratorInterface::class),
            childProcessor: $this->createStub(ChildProcessorInterface::class),
            logger: $logger,
        );

        $multiProcessor = new MultiProcessor($settings);

        new ReflectionProperty(MultiProcessor::class, 'startTime')
            ->setValue($multiProcessor, time() - ((25 * 3600) + 60));

        new ReflectionMethod(MultiProcessor::class, 'finish')->invoke($multiProcessor);

        $this->assertMatchesRegularExpression(
            '/^25 hours, 1 minutes and \d+ seconds$/',
            (string) $logger->lastValueFor('Total time spent: {time}', 'time')
        );
    }

    #[Test]
    public function itReportsTheChunksItHandedOutRatherThanTheCountItStartedWith(): void
    {
        $logger = new RecordingLogger();

        $iterator = $this->createStub(IteratorInterface::class);
        $iterator
            ->method('getNumberOfChunks')
            ->willReturn(99);
        $iterator
            ->method('getChunk')
            ->willReturnOnConsecutiveCalls(
                new Chunk(['1']),
                new Chunk(['2']),
                new Chunk([])
            );

        $settings = new Settings(
            iterator: $iterator,
            childProcessor: $this->createStub(ChildProcessorInterface::class),
            logger: $logger,
            maxChildren: 2,
        );

        new MultiProcessor($settings)->run();

        $this->assertSame('99', $logger->lastValueFor('Chunks to process: {chunks}', 'chunks'));
        $this->assertSame('2', $logger->lastValueFor('Chunks handed to a child: {chunks}', 'chunks'));
    }
}
