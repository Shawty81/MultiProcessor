<?php

namespace MultiProcessor\ChildrenPool;

class Child
{
    public function __construct(
        public readonly int $pid,
        /**
         * @var mixed[]
         */
        public readonly array $chunk
    ) {}
}
