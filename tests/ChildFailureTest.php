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
    use FixtureRunner;

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
}
