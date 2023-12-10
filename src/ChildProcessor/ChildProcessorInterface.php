<?php

namespace MultiProcessor\ChildProcessor;

use MultiProcessor\Queue\Chunk;

interface ChildProcessorInterface
{
    public function init(): void;
    public function process(Chunk $chunk): void;
    public function finish(): void;

}
