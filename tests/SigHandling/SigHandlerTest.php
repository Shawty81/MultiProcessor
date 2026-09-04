<?php

declare(strict_types=1);

namespace MultiProcessor\Tests\SigHandling;

use Closure;
use MultiProcessor\SigHandling\SigHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SigHandlerTest extends TestCase
{
    #[Test]
    public function itCallShutdownCallback(): void
    {
        $handler = new SigHandler(posix_getpid());

        $called = false;
        $shouldBeCalled = function () use (&$called): void {
            $called = true;
        };

        $handler->registerShutdownCallback($shouldBeCalled);

        $handler->handle(SIGINT);

        $this->assertTrue($called);
    }

    #[Test]
    public function itDoesNothingWithSigChld(): void
    {
        $handler = new SigHandler(posix_getpid());

        $called = false;
        $shouldNotBeCalled = function () use (&$called): void {
            $called = true;
        };

        $handler->registerShutdownCallback($shouldNotBeCalled);

        $handler->handle(SIGCHLD);

        $this->assertFalse($called);
    }

    #[Test]
    public function itResetsTheHandlersAChildInheritedInsteadOfEndingIt(): void
    {
        $handler = $this->handlerThatMustNotShutDownInAChild();

        $report = $this->inAForkedChild(function () use ($handler): string {
            $handler->handle(SIGCHLD);

            return implode(' ', array_map(
                static fn(int $signal): string => pcntl_signal_get_handler($signal) === SIG_DFL ? 'default' : 'inherited',
                [SIGTERM, SIGINT, SIGCHLD]
            ));
        });

        $this->assertSame('default default default', $report);
    }

    #[Test]
    public function itLeavesAShutdownSignalToTheDefaultDispositionOfAChild(): void
    {
        $handler = $this->handlerThatMustNotShutDownInAChild();

        $report = $this->inAForkedChild(function () use ($handler): string {
            $handler->handle(SIGTERM);

            return 'the child carried on past a SIGTERM';
        });

        $this->assertSame('', $report);
    }

    private function handlerThatMustNotShutDownInAChild(): SigHandler
    {
        $handler = new SigHandler(posix_getpid());

        $handler->registerShutdownCallback(static function (): void {
            throw new RuntimeException('A child must never shut down on behalf of its parent.');
        });

        return $handler;
    }

    /**
     * Runs the closure in a forked child and returns what it reported back, or an empty
     * string when the child never got as far as reporting anything.
     */
    private function inAForkedChild(Closure $closure): string
    {
        $reportFile = tempnam(sys_get_temp_dir(), 'multiprocessor-sighandler-');

        if ($reportFile === false) {
            $this->fail('Could not create a file for the child to report through.');
        }

        $pid = pcntl_fork();

        if ($pid === -1) {
            $this->fail('Could not fork.');
        }

        if ($pid === 0) {
            file_put_contents($reportFile, $closure());

            // SIGKILL rather than exit(), so the child cannot reach the shutdown of the test runner
            posix_kill(posix_getpid(), SIGKILL);
        }

        pcntl_waitpid($pid, $status);

        $report = (string) file_get_contents($reportFile);

        unlink($reportFile);

        return $report;
    }
}
