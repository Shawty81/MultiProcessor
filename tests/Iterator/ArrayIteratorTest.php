<?php

namespace MultiProcessor\Tests\Iterator;

use MultiProcessor\Iterator\ArrayIterator;
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
        $iterator->setChunkSize($size);

        $iterator->init();

        $chunk = $iterator->getChunk();

        $this->assertCount($size, $chunk);
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
        $iterator->setChunkSize($size);

        $iterator->init();

        $chunk = $iterator->getChunk();
        $chunk2 = $iterator->getChunk();

        $this->assertCount($size, $chunk);
        $this->assertCount($size, $chunk2);
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
        $iterator->setChunkSize($size);

        $iterator->init();

        $chunk = $iterator->getChunk();

        $this->assertCount($expected, $chunk);
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
     * @param mixed[] $expected1
     * @param mixed[] $expected2
     * @param mixed[] $expected3
     *
     * @return void
     */
    public function itCreatesTheCorrectChunksInOrder(array $data, int $size, array $expected1, array $expected2, array $expected3): void
    {
        $iterator = new ArrayIterator();
        $iterator->setArray($data);
        $iterator->setChunkSize($size);

        $iterator->init();

        $chunk1 = $iterator->getChunk();
        $chunk2 = $iterator->getChunk();
        $chunk3 = $iterator->getChunk();

        $this->assertSame($expected1, $chunk1);
        $this->assertSame($expected2, $chunk2);
        $this->assertSame($expected3, $chunk3);
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
                'expected1' => ['1', '2'],
                'expected2' => ['3', '4'],
                'expected3' => ['5'],
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
        $iterator->setChunkSize($chunkSize);

        $this->assertSame($expected, $iterator->getNumberOfChunks());
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
