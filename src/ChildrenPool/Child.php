<?php

namespace MultiProcessor\ChildrenPool;

use MultiProcessor\Queue\Chunk;

readonly class Child
{
    public function __construct(
        public int $pid,
        public Chunk $chunk
    ) {}
}
