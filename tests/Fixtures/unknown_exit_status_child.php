<?php

/**
 * Fills the pool with three children. The first exits with a status the parent does not
 * know, the other two outlive that moment and write to MP_MARKER_FILE if they are still
 * running a second later.
 */

use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\Iterator\ArrayIterator;
use MultiProcessor\Log\CommandLineLogger;
use MultiProcessor\MultiProcessor;
use MultiProcessor\Queue\Chunk;
use MultiProcessor\Settings;

require __DIR__ . '/../../vendor/autoload.php';

$markerFile = getenv('MP_MARKER_FILE');

if ($markerFile === false) {
    throw new RuntimeException('This fixture needs MP_MARKER_FILE to be set.');
}

$iterator = new ArrayIterator();
$iterator->setArray(['exit-with-42', 'outlives-the-parent', 'outlives-the-parent-too']);

$childProcessor = new class ($markerFile) implements ChildProcessorInterface {
    public function __construct(
        private readonly string $markerFile
    ) {}

    public function init(): void {}

    public function process(Chunk $chunk): void
    {
        if ($chunk->data === ['exit-with-42']) {
            exit(42);
        }

        sleep(1);

        file_put_contents($this->markerFile, "still alive\n", FILE_APPEND);
    }

    public function finish(): void {}
};

$settings = new Settings(
    iterator: $iterator,
    childProcessor: $childProcessor,
    logger: new CommandLineLogger(),
    chunkSize: 1,
    maxChildren: 3,
);

new MultiProcessor($settings)->run();
