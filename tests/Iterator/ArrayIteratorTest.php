<?php

namespace MultiProcessor\Tests\Iterator;

use MultiProcessor\Iterator\ArrayIterator;
use MultiProcessor\Queue\Chunk;
use PHPUnit\Framework\TestCase;

class ArrayIteratorTest extends TestCase
{
    /**
     * @test
     * @dataProvider itCreatesTheCorrectSizedChunkProvider
     *
     * @param mixed[] $data
     * @param int $size
     *
     * @return void
     */
    public function itCreatesTheCorrectSizedChunk(array $data, int $size): void
    {
        $iterator = new ArrayIterator();
        $iterator->setArray($data);

        $iterator->init();

        $chunk = $iterator->getChunk($size);

        $this->assertCount($size, $chunk->data);
    }

    /**
     * @return mixed[]
     */
    public static function itCreatesTheCorrectSizedChunkProvider(): array
    {
        return [
            [
                'data' => ['1', '2', '3', '4', '5'],
                'size' => 2,
            ],
            [
                'data' => ['1', '2', '3', '4', '5'],
                'size' => 3,
            ],
            [
                'data' => ['1', '2', '3', '4', '5'],
                'size' => 4,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider itCreatesNextChunkProvider
     *
     * @param mixed[] $data
     * @param int $size
     *
     * @return void
     */
    public function itCreatesNextChunk(array $data, int $size): void
    {
        $iterator = new ArrayIterator();
        $iterator->setArray($data);

        $iterator->init();

        $chunk = $iterator->getChunk($size);
        $chunk2 = $iterator->getChunk($size);

        $this->assertCount($size, $chunk->data);
        $this->assertCount($size, $chunk2->data);
    }

    /**
     * @return mixed[]
     */
    public static function itCreatesNextChunkProvider(): array
    {
        return [
            [
                'data' => ['1', '2', '3', '4', '5'],
                'size' => 2,
            ],
            [
                'data' => ['1', '2', '3', '4', '5', '6'],
                'size' => 3,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider itCreatesSmallerChunkWhenNotEnoughItemsProvider
     *
     * @param mixed[] $data
     * @param int $size
     *
     * @return void
     */
    public function itCreatesSmallerChunkWhenNotEnoughItems(array $data, int $size, int $expected): void
    {
        $iterator = new ArrayIterator();
        $iterator->setArray($data);

        $iterator->init();

        $chunk = $iterator->getChunk($size);

        $this->assertCount($expected, $chunk->data);
    }

    /**
     * @return mixed[]
     */
    public static function itCreatesSmallerChunkWhenNotEnoughItemsProvider(): array
    {
        return [
            [
                'data' => ['1', '2', '3', '4', '5'],
                'size' => 6,
                'expected' => 5,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider itCreatesTheCorrectChunksInOrderProvider
     *
     * @param mixed[] $data
     * @param int $size
     * @param Chunk $expected1
     * @param Chunk $expected2
     * @param Chunk $expected3
     *
     * @return void
     */
    public function itCreatesTheCorrectChunksInOrder(array $data, int $size, Chunk $expected1, Chunk $expected2, Chunk $expected3): void
    {
        $iterator = new ArrayIterator();
        $iterator->setArray($data);

        $iterator->init();

        $chunk1 = $iterator->getChunk($size);
        $chunk2 = $iterator->getChunk($size);
        $chunk3 = $iterator->getChunk($size);

        $this->assertSame($expected1->data, $chunk1->data);
        $this->assertSame($expected2->data, $chunk2->data);
        $this->assertSame($expected3->data, $chunk3->data);
    }

    /**
     * @return mixed[]
     */
    public static function itCreatesTheCorrectChunksInOrderProvider(): array
    {
        return [
            [
                'data' => ['1', '2', '3', '4', '5'],
                'size' => 2,
                'expected1' => new Chunk(['1', '2']),
                'expected2' => new Chunk(['3', '4']),
                'expected3' => new Chunk(['5']),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider itCalculatesNumberOfChunkProvider
     *
     * @param mixed[] $data
     * @param int $chunkSize
     * @param int $expected
     *
     * @return void
     */
    public function itCalculatesNumberOfChunk(array $data, int $chunkSize, int $expected): void
    {
        $iterator = new ArrayIterator();
        $iterator->setArray($data);

        $this->assertSame($expected, $iterator->getNumberOfChunks($chunkSize));
    }

    /**
     * @return mixed[]
     */
    public static function itCalculatesNumberOfChunkProvider(): array
    {
        return [
            [
                'data' => ['1', '2', '3', '4', '5'],
                'size' => 1,
                'expected' => 5,
            ],
            [
                'data' => ['1', '2', '3', '4', '5'],
                'size' => 2,
                'expected' => 3,
            ],
            [
                'data' => ['1', '2', '3', '4', '5'],
                'size' => 3,
                'expected' => 2,
            ],
            [
                'data' => ['1', '2', '3', '4', '5'],
                'size' => 4,
                'expected' => 2,
            ],
            [
                'data' => ['1', '2', '3', '4', '5'],
                'size' => 5,
                'expected' => 1,
            ],
            [
                'data' => ['1', '2', '3', '4', '5'],
                'size' => 6,
                'expected' => 1,
            ],
        ];
    }
}
