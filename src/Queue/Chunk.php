<?php

namespace MultiProcessor\Queue;

final readonly class Chunk
{
    public function __construct(
        /** @var mixed[]  */
        public array $data
    ) {}
}
