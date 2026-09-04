<?php

declare(strict_types=1);

namespace MultiProcessor\Tests\Iterator;

use MultiProcessor\Iterator\ArrayIterator;
use MultiProcessor\Queue\Chunk;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ArrayIteratorTest extends TestCase
{
    /**
     * @param mixed[] $data
     */
    #[Test]
    #[DataProvider('itCreatesTheCorrectSizedChunkProvider')]
    public function itCreatesTheCorrectSizedChunk(array $data, int $size): void
    {
        $iterator = new ArrayIterator();
        $iterator->setArray($data);

        $iterator->init();

        $chunk = $iterator->getChunk($size);

        $this->assertCount($size, $chunk->data);
    }

    /**
     * @return array<int, array{data: mixed[], size: int}>
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
     * @param mixed[] $data
     */
    #[Test]
    #[DataProvider('itCreatesNextChunkProvider')]
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
     * @return array<int, array{data: mixed[], size: int}>
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
     * @param mixed[] $data
     */
    #[Test]
    #[DataProvider('itCreatesSmallerChunkWhenNotEnoughItemsProvider')]
    public function itCreatesSmallerChunkWhenNotEnoughItems(array $data, int $size, int $expected): void
    {
        $iterator = new ArrayIterator();
        $iterator->setArray($data);

        $iterator->init();

        $chunk = $iterator->getChunk($size);

        $this->assertCount($expected, $chunk->data);
    }

    /**
     * @return array<int, array{data: mixed[], size: int, expected: int}>
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
     * @param mixed[] $data
     */
    #[Test]
    #[DataProvider('itCreatesTheCorrectChunksInOrderProvider')]
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
     * @return array<int, array{data: mixed[], size: int, expected1: Chunk, expected2: Chunk, expected3: Chunk}>
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
     * @param mixed[] $data
     */
    #[Test]
    #[DataProvider('itCalculatesNumberOfChunkProvider')]
    public function itCalculatesNumberOfChunk(array $data, int $chunkSize, int $expected): void
    {
        $iterator = new ArrayIterator();
        $iterator->setArray($data);

        $this->assertSame($expected, $iterator->getNumberOfChunks($chunkSize));
    }

    /**
     * @return array<int, array{data: mixed[], chunkSize: int, expected: int}>
     */
    public static function itCalculatesNumberOfChunkProvider(): array
    {
        return [
            [
                'data' => ['1', '2', '3', '4', '5'],
                'chunkSize' => 1,
                'expected' => 5,
            ],
            [
                'data' => ['1', '2', '3', '4', '5'],
                'chunkSize' => 2,
                'expected' => 3,
            ],
            [
                'data' => ['1', '2', '3', '4', '5'],
                'chunkSize' => 3,
                'expected' => 2,
            ],
            [
                'data' => ['1', '2', '3', '4', '5'],
                'chunkSize' => 4,
                'expected' => 2,
            ],
            [
                'data' => ['1', '2', '3', '4', '5'],
                'chunkSize' => 5,
                'expected' => 1,
            ],
            [
                'data' => ['1', '2', '3', '4', '5'],
                'chunkSize' => 6,
                'expected' => 1,
            ],
        ];
    }

    /**
     * Every one of these shapes is a perfectly ordinary PHP array of work, and all of the
     * records in it have to come back out.
     *
     * @param mixed[] $data
     * @param mixed[] $expected
     */
    #[Test]
    #[DataProvider('itYieldsEveryRecordProvider')]
    public function itYieldsEveryRecord(array $data, array $expected, int $chunkSize, int $expectedChunks): void
    {
        $iterator = new ArrayIterator();
        $iterator->setArray($data);

        $this->assertSame($expectedChunks, $iterator->getNumberOfChunks($chunkSize));

        $iterator->init();

        $yielded = [];

        while (($chunk = $iterator->getChunk($chunkSize))->data !== []) {
            $yielded = array_merge($yielded, $chunk->data);

            if (count($yielded) > count($expected)) {
                $this->fail('The iterator yielded more records than the array holds.');
            }
        }

        $this->assertSame($expected, $yielded);
    }

    /**
     * @return array<string, array{data: mixed[], expected: mixed[], chunkSize: int, expectedChunks: int}>
     */
    public static function itYieldsEveryRecordProvider(): array
    {
        $withAGap = ['first', 'second', 'third'];
        unset($withAGap[1]);

        return [
            'plain list' => [
                'data' => ['a', 'b', 'c', 'd', 'e'],
                'expected' => ['a', 'b', 'c', 'd', 'e'],
                'chunkSize' => 2,
                'expectedChunks' => 3,
            ],
            'list containing false' => [
                'data' => ['a', false, 'c'],
                'expected' => ['a', false, 'c'],
                'chunkSize' => 2,
                'expectedChunks' => 2,
            ],
            'list containing null' => [
                'data' => ['a', null, 'c', 'd', 'e'],
                'expected' => ['a', null, 'c', 'd', 'e'],
                'chunkSize' => 2,
                'expectedChunks' => 3,
            ],
            'string keys' => [
                'data' => ['first' => 'a', 'second' => 'b', 'third' => 'c'],
                'expected' => ['a', 'b', 'c'],
                'chunkSize' => 2,
                'expectedChunks' => 2,
            ],
            'list with a gap' => [
                'data' => $withAGap,
                'expected' => ['first', 'third'],
                'chunkSize' => 2,
                'expectedChunks' => 1,
            ],
            'array_filter() result' => [
                'data' => array_filter(['a', '', 'c', 'd']),
                'expected' => ['a', 'c', 'd'],
                'chunkSize' => 2,
                'expectedChunks' => 2,
            ],
            'rows keyed by database id' => [
                'data' => [17 => 'a', 42 => 'b', 99 => 'c'],
                'expected' => ['a', 'b', 'c'],
                'chunkSize' => 2,
                'expectedChunks' => 2,
            ],
        ];
    }
}
