<?php

declare(strict_types=1);

namespace MultiProcessor\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Covers what the parent does when a child does not exit cleanly.
 *
 * These scenarios only exist once a process has really been forked, so every case here
 * runs a fixture script in its own process and reads back what the consumer would see on
 * their command line.
 */
class ChildFailureTest extends TestCase
{
    private string $markerFile;

    protected function setUp(): void
    {
        $this->markerFile = sys_get_temp_dir() . '/multiprocessor-orphans-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        if (is_file($this->markerFile)) {
            unlink($this->markerFile);
        }
    }

    #[Test]
    public function itLogsTheExceptionAChildDiedOf(): void
    {
        $output = $this->runFixture('throwing_child.php');

        $this->assertStringContainsString('RuntimeException', $output);
        $this->assertStringContainsString('the child blew up', $output);
        $this->assertStringContainsString('#0 ', $output);
    }

    #[Test]
    public function itStopsRetryingAChunkThatKeepsFailing(): void
    {
        $output = $this->runFixture('throwing_child.php');

        $this->assertSame(2, substr_count($output, 'exited with an error'));
        $this->assertStringContainsString('giving up on it', $output);
        $this->assertStringContainsString('MultiProcessor done!', $output);
    }

    #[Test]
    public function itHonoursAConfiguredRetryLimit(): void
    {
        $output = $this->runFixture('throwing_child.php', ['MP_MAX_RETRIES' => '3']);

        $this->assertSame(4, substr_count($output, 'exited with an error'));
        $this->assertStringContainsString('giving up on it', $output);
        $this->assertStringContainsString('MultiProcessor done!', $output);
    }

    #[Test]
    public function itCountsEveryRetryInTheSummary(): void
    {
        $output = $this->runFixture('throwing_child.php', ['MP_MAX_RETRIES' => '3']);

        $this->assertStringContainsString('Chunks to process: 1', $output);
        $this->assertStringContainsString('Chunks handed to a child: 4', $output);
    }

    #[Test]
    public function itReportsAndRetriesAChildThatWasKilledBySignal(): void
    {
        $output = $this->runFixture('signalled_child.php');

        $this->assertSame(2, substr_count($output, 'was killed by signal 9'));
        $this->assertStringContainsString('giving up on it', $output);
        $this->assertStringContainsString('MultiProcessor done!', $output);
    }

    #[Test]
    public function itKillsTheRemainingChildrenOnAnUnknownExitStatus(): void
    {
        $output = $this->runFixture(
            'unknown_exit_status_child.php',
            ['MP_MARKER_FILE' => $this->markerFile]
        );

        $this->assertStringContainsString('exited with unknown status [ 42 ]', $output);
        $this->assertStringContainsString('MultiProcessor aborted successfully!', $output);
        $this->assertFileDoesNotExist($this->markerFile);
    }

    /**
     * Runs a fixture and returns everything it wrote to stdout and stderr.
     *
     * Reading until both pipes reach end of file rather than until the process exits is
     * deliberate: children the parent leaves behind keep holding those pipes open, so a
     * run that orphans its children is waited out instead of being sampled too early.
     *
     * @param array<string, string> $environment
     */
    private function runFixture(string $script, array $environment = [], int $timeoutSeconds = 30): string
    {
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/Fixtures/' . $script],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            array_merge(getenv(), $environment)
        );

        if ($process === false) {
            $this->fail('Could not start fixture ' . $script);
        }

        foreach ($pipes as $pipe) {
            stream_set_blocking($pipe, false);
        }

        $output = '';
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
                    sprintf('Fixture %s did not finish within %d seconds. Output so far:%s%s', $script, $timeoutSeconds, PHP_EOL, $output)
                );
            }

            usleep(5000);
        }

        proc_close($process);

        return $output;
    }
}
