<?php

namespace MultiProcessor\ChildProcessor;

interface ChildProcessorInterface
{
    public function init(): void;

    /**
     * @param array<mixed> $chunk
     * @return void
     */
    public function process(array $chunk): void;
    public function finish(): void;

}
