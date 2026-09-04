<?php

declare(strict_types=1);

namespace MultiProcessor\Queue;

final readonly class Chunk
{
    public function __construct(
        /** @var mixed[]  */
        public array $data,
        /** The number of times this chunk was handed to a child again after a failure. */
        public int $retries = 0
    ) {}

    public function retried(): self
    {
        return new self($this->data, $this->retries + 1);
    }
}
