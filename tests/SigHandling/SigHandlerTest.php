<?php

namespace MultiProcessor\Tests\SigHandling;

use MultiProcessor\SigHandling\SigHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

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
}
