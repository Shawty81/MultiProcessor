<?php

namespace MultiProcessor\ChildProcessor;

use Psr\Log\LoggerAwareTrait;

abstract class AbstractChildProcessor implements ChildProcessorInterface
{
    use LoggerAwareTrait;
}
