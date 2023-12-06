<?php

namespace MultiProcessor\Iterator;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

abstract class AbstractIterator implements IteratorInterface
{
    use LoggerAwareTrait;

    protected int $chunkSize = 1;

    public function setChunkSize(int $size): void
    {
        $this->chunkSize = $size;
    }

}
