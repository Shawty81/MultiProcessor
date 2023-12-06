<?php

namespace MultiProcessor\ChildProcessor;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

abstract class AbstractChildProcessor implements ChildProcessorInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

}
