<?php

declare(strict_types=1);

namespace MultiProcessor\Iterator;

use MultiProcessor\Queue\Chunk;
use Override;

final class ArrayIterator implements IteratorInterface
{
    /**
     * @var array<mixed>
     */
    private array $array = [];
    private int $position;

    #[Override]
    public function init(): void
    {
        $this->position = 0;
    }

    #[Override]
    public function getChunk(int $size): Chunk
    {
        $data = array_slice($this->array, $this->position, $size);
        $this->position += count($data);

        return new Chunk($data);
    }

    /**
     * @param array<mixed> $array
     * @return void
     */
    public function setArray(array $array): void
    {
        // Chunks are handed out by position, so anything that is not a zero indexed list
        // - string keys, rows keyed by their id, the gaps unset() and array_filter() leave
        // behind - has to be renumbered here or its records are never reached.
        $this->array = array_values($array);
    }

    #[Override]
    public function getNumberOfChunks(int $chunkSize): int
    {
        return (int) ceil(count($this->array) / $chunkSize);
    }

    #[Override]
    public function dropConnections(): void
    {
        // noop
    }

    #[Override]
    public function finish(): void
    {
        // noop
    }

}
