<?php

namespace MultiProcessor\Queue;

class Queue
{
    /** @var Chunk[] */
    private array $chunks = [];

    public function addChunk(Chunk $chunk): void
    {
        $this->chunks[] = $chunk;
    }

    public function getChunk(): ?Chunk
    {
        return array_shift($this->chunks);
    }

    public function size(): int
    {
        return count($this->chunks);
    }
}
