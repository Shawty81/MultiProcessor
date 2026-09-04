<?php

declare(strict_types=1);

namespace MultiProcessor\SigHandling;

use Closure;

final class SigHandler
{
    private Closure $shutdownCallback;

    /**
     * The signals this handler takes over, mapped to whether a blocking call interrupted
     * by that signal should be resumed once the handler returns.
     *
     * A shutdown signal must not resume it. The parent spends nearly all of its time
     * blocked in pcntl_waitpid(), and a resumed wait sits there until a child happens to
     * exit, which is exactly as long as the shutdown would be delayed.
     *
     * SIGCHLD does resume it, because there the blocking wait is the thing that reaps the
     * child the signal is about.
     *
     * @var array<int, bool>
     */
    private const array SIGNALS = [
        SIGTERM => false,
        SIGINT => false,
        SIGCHLD => true,
    ];

    public function __construct(
        private readonly int $parentPid
    ) {
        pcntl_async_signals(true);

        foreach (self::SIGNALS as $signal => $restartSyscalls) {
            pcntl_signal($signal, [$this, 'handle'], $restartSyscalls);
        }
    }

    public function registerShutdownCallback(Closure $closure): void
    {
        $this->shutdownCallback = $closure;
    }

    public function handle(int $signal): void
    {
        if (posix_getpid() === $this->parentPid) {
            $this->handleParent($signal);

            return;
        }

        $this->handleChild($signal);
    }

    private function handleParent(int $signal): void
    {
        if ($signal === SIGCHLD) {
            // This is the signal that the child stopped, nothing to do here.
            return;
        }

        call_user_func($this->shutdownCallback);
    }

    /**
     * A forked child inherits these handlers but has no business acting on the parent's
     * behalf, so it hands the signals back to the operating system and lets the default
     * disposition decide, which is what it would have been without the fork.
     */
    private function handleChild(int $signal): void
    {
        foreach (array_keys(self::SIGNALS) as $inherited) {
            pcntl_signal($inherited, SIG_DFL);
        }

        posix_kill(posix_getpid(), $signal);
    }
}
