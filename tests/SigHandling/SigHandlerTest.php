<?php

namespace MultiProcessor\Tests\SigHandling;

use MultiProcessor\SigHandling\SigHandler;
use PHPUnit\Framework\TestCase;

class SigHandlerTest extends TestCase
{
    /**
     * @test
     */
    public function itCallShutdownCallback(): void
    {
        $handler = new SigHandler(posix_getpid());

        $shouldBeCalled = function () {
            $this->assertTrue(true);
        };

        $handler->registerShutdownCallback($shouldBeCalled);

        $handler->handle(SIGINT);
    }

    /**
     * @test
     */
    public function itDoesNothingWithSigChld(): void
    {
        $handler = new SigHandler(posix_getpid());

        $shouldNotBeCalled = function () {
            $this->assertTrue(false);
        };

        $handler->registerShutdownCallback($shouldNotBeCalled);

        $handler->handle(SIGCHLD);

        $this->assertTrue(true);
    }
}
