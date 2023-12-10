<?php

namespace MultiProcessor;

use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\Iterator\IteratorInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
final class Settings
{
    private IteratorInterface $iterator;
    private ChildProcessorInterface $childProcessor;
    private ?LoggerInterface $logger = null;
    private int $maxChildren = 1;
    private int $chunkSize = 10;
    private bool $retryOnFatal = true;

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

    public function validate(): void
    {
        if (!isset($this->iterator)) {
            throw new RuntimeException('Your MultiProcessor Settings are missing an Iterator');
        }

        if (!isset($this->childProcessor)) {
            throw new RuntimeException('Your MultiProcessor Settings are missing an ChildProcessor');
        }
    }
}
