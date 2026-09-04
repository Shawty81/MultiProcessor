<?php

declare(strict_types=1);

namespace MultiProcessor\ChildrenPool;

use MultiProcessor\Queue\Chunk;

final readonly class Child
{
    public function __construct(
        public int $pid,
        public Chunk $chunk
    ) {}
}
