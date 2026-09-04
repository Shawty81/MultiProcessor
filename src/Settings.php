<?php

namespace MultiProcessor;

use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\Exception\InvalidSettingsException;
use MultiProcessor\Iterator\IteratorInterface;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings("PHPMD.ExcessivePublicCount")
 */
final class Settings
{
    private IteratorInterface $iterator;
    private ChildProcessorInterface $childProcessor;
    private ?LoggerInterface $logger = null;
    private int $maxChildren = 1;
    private int $chunkSize = 10;
    private bool $retryOnFatal = true;
    private int $maxRetries = 1;

    private bool $exitOnFatal = false;

    public function getIterator(): IteratorInterface
    {
        return $this->iterator;
    }

    public function setIterator(IteratorInterface $iterator): self
    {
        $this->iterator = $iterator;

        return $this;
    }

    public function getChildProcessor(): ChildProcessorInterface
    {
        return $this->childProcessor;
    }

    public function setChildProcessor(ChildProcessorInterface $childProcessor): self
    {
        $this->childProcessor = $childProcessor;

        return $this;
    }

    public function getMaxChildren(): int
    {
        return $this->maxChildren;
    }

    public function setMaxChildren(int $maxChildren): self
    {
        $this->maxChildren = $maxChildren;

        return $this;
    }

    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    public function setChunkSize(int $chunkSize): self
    {
        $this->chunkSize = $chunkSize;

        return $this;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function setMaxRetries(int $maxRetries): self
    {
        $this->maxRetries = $maxRetries;

        return $this;
    }

    public function isRetryOnFatal(): bool
    {
        return $this->retryOnFatal;
    }

    public function setRetryOnFatal(bool $retryOnFatal): self
    {
        $this->retryOnFatal = $retryOnFatal;

        return $this;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function setLogger(?LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function isExitOnFatal(): bool
    {
        return $this->exitOnFatal;
    }

    public function setExitOnFatal(bool $exitOnFatal): self
    {
        $this->exitOnFatal = $exitOnFatal;

        return $this;
    }

    public function validate(): void
    {
        if (!isset($this->iterator)) {
            throw new InvalidSettingsException('Your MultiProcessor Settings are missing an Iterator');
        }

        if (!isset($this->childProcessor)) {
            throw new InvalidSettingsException('Your MultiProcessor Settings are missing an ChildProcessor');
        }

        if ($this->chunkSize < 1) {
            throw new InvalidSettingsException('Your MultiProcessor Settings need a chunkSize of at least 1');
        }

        if ($this->maxChildren < 1) {
            throw new InvalidSettingsException('Your MultiProcessor Settings need a maxChildren of at least 1');
        }

        if ($this->maxRetries < 0) {
            throw new InvalidSettingsException('Your MultiProcessor Settings need a maxRetries of 0 or more');
        }
    }
}
