<?php

namespace MultiProcessor\Iterator;

interface IteratorInterface
{
    public function init(): void;
    public function finish(): void;

    /**
     * @return array<mixed>
     */
    public function getChunk(int $size): array;
    public function getNumberOfChunks(int $chunkSize): int;

    public function dropConnections(): void;

}
