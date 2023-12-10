<?php

namespace MultiProcessor\Iterator;

use MultiProcessor\Queue\Chunk;

final class ArrayIterator implements IteratorInterface
{
    /**
     * @var array<mixed>
     */
    private array $array = [];
    private int $position;

    public function init(): void
    {
        $this->position = 0;
    }

    public function getChunk(int $size): Chunk
    {
        $data = [];

        for ($i = 0; $i < $size; $i++) {
            if (!isset($this->array[$this->position])) {
                break;
            }

            $data[] = $this->array[$this->position++];
        }

        return new Chunk($data);
    }

    /**
     * @param array<mixed> $array
     * @return void
     */
    public function setArray(array $array): void
    {
        $this->array = $array;
    }

    public function getNumberOfChunks(int $chunkSize): int
    {
        return (int) ceil(count($this->array) / $chunkSize);
    }

    public function dropConnections(): void
    {
        // noop
    }

    public function finish(): void
    {
        // noop
    }

}
