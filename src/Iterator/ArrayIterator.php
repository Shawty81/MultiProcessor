<?php

namespace MultiProcessor\Iterator;

final class ArrayIterator extends AbstractIterator
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

    /**
     * @inheritDoc
     */
    public function getChunk(): array
    {
        $chunk = [];

        for ($i = 0; $i < $this->chunkSize; $i++) {
            if (!isset($this->array[$this->position])) {
                break;
            }

            $chunk[] = $this->array[$this->position++];
        }

        return $chunk;
    }

    /**
     * @param array<mixed> $array
     * @return void
     */
    public function setArray(array $array): void
    {
        $this->array = $array;
    }

    public function getNumberOfChunks(): int
    {
        return (int) ceil(count($this->array) / $this->chunkSize);
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
