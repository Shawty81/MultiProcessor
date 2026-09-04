<?php

declare(strict_types=1);

/**
 * Runs a single chunk through a ChildProcessor that always throws.
 *
 * Reads MP_MAX_RETRIES from the environment when the scenario needs a retry limit other
 * than the default.
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
    #[Override]
    public function init(): void {}

    #[Override]
    public function process(Chunk $chunk): void
    {
        throw new RuntimeException('the child blew up');
    }

    #[Override]
    public function finish(): void {}
};

$maxRetries = getenv('MP_MAX_RETRIES');

$settings = new Settings(
    iterator: $iterator,
    childProcessor: $childProcessor,
    logger: new CommandLineLogger(),
    chunkSize: 1,
    maxChildren: 1,
    maxRetries: $maxRetries === false ? 1 : (int) $maxRetries,
);

new MultiProcessor($settings)->run();
