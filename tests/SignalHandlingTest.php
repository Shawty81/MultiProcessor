<?php

declare(strict_types=1);

namespace MultiProcessor\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Covers what signals do to a running MultiProcessor.
 *
 * Signals are only delivered to a real process, and the interesting moments are the ones
 * where the parent is blocked or where a child has children of its own, so every case
 * here runs a fixture script in its own process.
 */
class SignalHandlingTest extends TestCase
{
    #[Test]
    public function itShutsDownWhileItIsWaitingOnAChild(): void
    {
        $output = '';
        $process = $this->startFixture('interrupted_parent.php', $pipes);

        $pid = $this->readUntil($pipes, '/parent pid: (?<pid>\d+)/', 10, $output);

        $this->readUntil($pipes, '/the child is sleeping/', 10, $output);

        $startedWaiting = microtime(true);

        posix_kill((int) $pid, SIGINT);

        $this->readToTheEnd($process, $pipes, 20, $output);
        $waited = microtime(true) - $startedWaiting;

        $this->assertStringContainsString('Initiate killing of children.', $output);
        $this->assertStringContainsString('MultiProcessor aborted successfully!', $output);

        // The child sleeps for a minute. A shutdown that has to wait for it is no shutdown.
        $this->assertLessThan(10, $waited);
    }

    #[Test]
    public function itLetsAChildOutliveAWorkerOfItsOwn(): void
    {
        $output = '';
        $process = $this->startFixture('nested_fork_child.php', $pipes);

        $this->readToTheEnd($process, $pipes, 30, $output);

        $this->assertStringContainsString('the child outlived its own worker', $output);
        $this->assertStringNotContainsString('exited with an error', $output);
        $this->assertStringContainsString('MultiProcessor done!', $output);
    }

    /**
     * @param array<int, resource> $pipes
     * @param-out array<int, resource> $pipes
     * @return resource
     */
    private function startFixture(string $script, ?array &$pipes): mixed
    {
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/Fixtures/' . $script],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );

        if ($process === false) {
            $this->fail('Could not start fixture ' . $script);
        }

        foreach ($pipes as $pipe) {
            stream_set_blocking($pipe, false);
        }

        return $process;
    }

    /**
     * Reads until the fixture says what the caller is waiting for, and returns the first
     * capture group of the pattern that said it.
     *
     * @param array<int, resource> $pipes
     */
    private function readUntil(array $pipes, string $pattern, int $timeoutSeconds, string &$output): string
    {
        $deadline = microtime(true) + $timeoutSeconds;

        while (microtime(true) < $deadline) {
            foreach ($pipes as $pipe) {
                $output .= (string) fread($pipe, 8192);
            }

            if (preg_match($pattern, $output, $matches) === 1) {
                return $matches[1] ?? $matches[0];
            }

            usleep(5000);
        }

        $this->fail(sprintf('Never saw %s. Output so far:%s%s', $pattern, PHP_EOL, $output));
    }

    /**
     * Reads until both pipes reach end of file rather than until the process exits, so
     * that children the parent leaves behind, which keep those pipes open, are waited out
     * instead of the output being sampled too early.
     *
     * @param resource $process
     * @param array<int, resource> $pipes
     */
    private function readToTheEnd(mixed $process, array $pipes, int $timeoutSeconds, string &$output): void
    {
        $deadline = microtime(true) + $timeoutSeconds;

        while ($pipes !== []) {
            foreach ($pipes as $index => $pipe) {
                $output .= (string) fread($pipe, 8192);

                if (feof($pipe)) {
                    fclose($pipe);
                    unset($pipes[$index]);
                }
            }

            if (microtime(true) >= $deadline) {
                proc_terminate($process, SIGKILL);

                foreach ($pipes as $pipe) {
                    fclose($pipe);
                }

                proc_close($process);

                $this->fail(
                    sprintf('Fixture did not finish within %d seconds. Output so far:%s%s', $timeoutSeconds, PHP_EOL, $output)
                );
            }

            usleep(5000);
        }

        proc_close($process);
    }
}
