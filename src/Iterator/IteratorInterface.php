<?php

namespace MultiProcessor\Iterator;

interface IteratorInterface
{
    public function setChunkSize(int $size): void;
    public function init(): void;
    public function finish(): void;

    /**
     * @return array<mixed>
     */
    public function getChunk(): array;
    public function getNumberOfChunks(): int;

    public function dropConnections(): void;

}
