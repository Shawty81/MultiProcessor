<?php

/**
 * Runs a single chunk through a ChildProcessor that forks a worker of its own and reaps
 * it, which delivers a SIGCHLD to a process that is itself a child of the MultiProcessor.
 */

use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\Iterator\ArrayIterator;
use MultiProcessor\Log\CommandLineLogger;
use MultiProcessor\MultiProcessor;
use MultiProcessor\Queue\Chunk;
use MultiProcessor\Settings;

require __DIR__ . '/../../vendor/autoload.php';

$logger = new CommandLineLogger();

$iterator = new ArrayIterator();
$iterator->setArray(['the only row']);

$childProcessor = new class ($logger) implements ChildProcessorInterface {
    public function __construct(
        private readonly CommandLineLogger $logger
    ) {}

    public function init(): void {}

    public function process(Chunk $chunk): void
    {
        $worker = pcntl_fork();

        if ($worker === 0) {
            exit(0);
        }

        pcntl_waitpid($worker, $workerStatus);

        // Long enough for an inherited handler to have been dispatched on the SIGCHLD
        usleep(200000);

        $this->logger->info('the child outlived its own worker');
    }

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
