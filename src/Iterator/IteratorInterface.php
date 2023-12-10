<?php

namespace MultiProcessor\Iterator;

use MultiProcessor\Queue\Chunk;

interface IteratorInterface
{
    public function init(): void;
    public function finish(): void;
    public function getChunk(int $size): Chunk;
    public function getNumberOfChunks(int $chunkSize): int;
    public function dropConnections(): void;

}
