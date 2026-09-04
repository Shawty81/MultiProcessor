<?php

namespace MultiProcessor;

use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\Exception\InvalidSettingsException;
use MultiProcessor\Iterator\IteratorInterface;
use Psr\Log\LoggerInterface;

/**
 * These booleans configure a value object rather than switch a behaviour on a call, so
 * the rule that reads a defaulted boolean parameter as a flag argument does not apply.
 *
 * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
 */
final readonly class Settings
{
    public function __construct(
        public IteratorInterface $iterator,
        public ChildProcessorInterface $childProcessor,
        public ?LoggerInterface $logger = null,
        public int $maxChildren = 1,
        public int $chunkSize = 10,
        public bool $retryOnFatal = true,
        public int $maxRetries = 1,
        public bool $exitOnFatal = false,
    ) {
        if ($chunkSize < 1) {
            throw new InvalidSettingsException('Your MultiProcessor Settings need a chunkSize of at least 1');
        }

        if ($maxChildren < 1) {
            throw new InvalidSettingsException('Your MultiProcessor Settings need a maxChildren of at least 1');
        }

        if ($maxRetries < 0) {
            throw new InvalidSettingsException('Your MultiProcessor Settings need a maxRetries of 0 or more');
        }
    }
}
