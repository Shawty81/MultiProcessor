<?php

declare(strict_types=1);

/**
 * Hands a single chunk to a child that sleeps far longer than the test is willing to
 * wait, so the parent is sitting in a blocking pcntl_waitpid() when the signal arrives.
 *
 * Announces its own pid first, and then announces the child, so a test knows both who to
 * signal and when the parent has reached the wait.
 */

use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\Iterator\ArrayIterator;
use MultiProcessor\Log\CommandLineLogger;
use MultiProcessor\MultiProcessor;
use MultiProcessor\Queue\Chunk;
use MultiProcessor\Settings;

require __DIR__ . '/../../vendor/autoload.php';

const CHILD_SLEEP_SECONDS = 60;

$logger = new CommandLineLogger();

$logger->info('parent pid: {pid}', ['pid' => posix_getpid()]);

$iterator = new ArrayIterator();
$iterator->setArray(['the only row']);

$childProcessor = new class ($logger) implements ChildProcessorInterface {
    public function __construct(
        private readonly CommandLineLogger $logger
    ) {}

    #[Override]
    public function init(): void {}

    #[Override]
    public function process(Chunk $chunk): void
    {
        $this->logger->info('the child is sleeping');

        sleep(CHILD_SLEEP_SECONDS);
    }

    #[Override]
    public function finish(): void {}
};

$settings = new Settings(
    iterator: $iterator,
    childProcessor: $childProcessor,
    logger: $logger,
    chunkSize: 1,
    maxChildren: 1,
);

new MultiProcessor($settings)->run();
