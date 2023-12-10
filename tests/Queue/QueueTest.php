<?php

namespace MultiProcessor\Tests\Queue;

use MultiProcessor\Queue\Chunk;
use MultiProcessor\Queue\Queue;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    /**
     * @test
     */
    public function itStoresAndGetsChunks(): void
    {
        $queue = new Queue();

        $expected = new Chunk(['1']);

        $queue->addChunk($expected);
        $result = $queue->getChunk();

        $this->assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function itReturnsNullWhenNoQueuedChunks(): void
    {
        $queue = new Queue();

        $result = $queue->getChunk();

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function itReturnsNullAfterAllChunksAreGotten(): void
    {
        $queue = new Queue();

        $queue->addChunk(new Chunk(['1']));
        $queue->getChunk();
        $result = $queue->getChunk();

        $this->assertNull($result);
    }
}
