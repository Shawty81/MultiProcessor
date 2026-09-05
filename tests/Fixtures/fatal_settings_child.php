<?php

declare(strict_types=1);

/**
 * Runs three chunks, the first of which always throws, under whatever combination of
 * retryOnFatal, exitOnFatal and maxRetries the environment asks for.
 *
 * The two chunks behind the poison one announce themselves, so a test can tell whether
 * the run carried on past the failure or was cut short by it.
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
$iterator->setArray(['poison', 'second row', 'third row']);

$childProcessor = new class ($logger) implements ChildProcessorInterface {
    public function __construct(
        private readonly CommandLineLogger $logger
    ) {}

    #[Override]
    public function init(): void {}

    #[Override]
    public function process(Chunk $chunk): void
    {
        if ($chunk->data === ['poison']) {
            throw new RuntimeException('the poison chunk blew up');
        }

        $this->logger->info('processed {row}', ['row' => $chunk->data[0]]);
    }

    #[Override]
    public function finish(): void {}
};

$maxRetries = getenv('MP_MAX_RETRIES');

$settings = new Settings(
    iterator: $iterator,
    childProcessor: $childProcessor,
    logger: $logger,
    chunkSize: 1,
    maxChildren: 1,
    retryOnFatal: getenv('MP_RETRY_ON_FATAL') !== '0',
    maxRetries: $maxRetries === false ? 1 : (int) $maxRetries,
    exitOnFatal: getenv('MP_EXIT_ON_FATAL') === '1',
);

new MultiProcessor($settings)->run();
