<?php

/**
 * Runs a single chunk through a ChildProcessor that kills its own process, the way an
 * out of memory killer or a segfault would.
 */

use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\Iterator\ArrayIterator;
use MultiProcessor\Log\CommandLineLogger;
use MultiProcessor\MultiProcessor;
use MultiProcessor\Queue\Chunk;
use MultiProcessor\Settings;

require __DIR__ . '/../../vendor/autoload.php';

$iterator = new ArrayIterator();
$iterator->setArray(['the only row']);

$childProcessor = new class implements ChildProcessorInterface {
    public function init(): void {}

    public function process(Chunk $chunk): void
    {
        posix_kill(posix_getpid(), SIGKILL);
    }

    public function finish(): void {}
};

$settings = new Settings(
    iterator: $iterator,
    childProcessor: $childProcessor,
    logger: new CommandLineLogger(),
    chunkSize: 1,
    maxChildren: 1,
);

new MultiProcessor($settings)->run();
