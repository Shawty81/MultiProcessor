<?php

namespace MultiProcessor\SigHandling;

use Closure;

final class SigHandler
{
    private Closure $shutdownCallback;

    private const SIGNALS = [
        SIGTERM,
        SIGINT,
        SIGCHLD,
    ];

    public function __construct(
        private readonly int $parentPid
    ) {
        $handler = [$this, 'handle'];
        foreach (self::SIGNALS as $signal) {
            pcntl_signal($signal, $handler);
        }
    }

    public function registerShutdownCallback(Closure $closure): void
    {
        $this->shutdownCallback = $closure;
    }

    /**
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function handle(int $signal): void
    {
        // Is parent
        if (posix_getpid() === $this->parentPid) {
            $this->handleParent($signal);
            return;
        }

        // Is child
        exit;
    }

    private function handleParent(int $signal): void
    {
        if ($signal === SIGCHLD) {
            // This is the signal that the child stopped, nothing to do here.
            return;
        }

        call_user_func($this->shutdownCallback);
    }
}
