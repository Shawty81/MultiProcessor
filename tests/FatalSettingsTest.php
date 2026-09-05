<?php

declare(strict_types=1);

namespace MultiProcessor\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Covers what retryOnFatal and exitOnFatal actually do to a run, and which of the two
 * wins when both are set.
 *
 * A chunk only fails fatally once it is in a child of its own, so every case here runs a
 * fixture script in its own process and reads back what the consumer would see on their
 * command line.
 */
class FatalSettingsTest extends TestCase
{
    use FixtureRunner;

    #[Test]
    public function itRetriesAFailingChunkUpToTheLimitAndThenDropsIt(): void
    {
        $output = $this->runFixture('fatal_settings_child.php', ['MP_MAX_RETRIES' => '2']);

        $this->assertSame(3, substr_count($output, 'exited with an error'));
        $this->assertSame(2, substr_count($output, 'to be retried'));
        $this->assertStringContainsString('failed 3 times, giving up on it', $output);
    }

    #[Test]
    public function itCarriesOnWithTheRestOfTheRunAfterDroppingAChunk(): void
    {
        $output = $this->runFixture('fatal_settings_child.php');

        $this->assertStringContainsString('processed second row', $output);
        $this->assertStringContainsString('processed third row', $output);
        $this->assertStringContainsString('MultiProcessor done!', $output);
        $this->assertStringContainsString('Chunks handed to a child: 4', $output);
    }

    /**
     * The retry is handed out before the iterator is read again, so the chunk that failed
     * is tried once more straight away rather than at the end of the run.
     */
    #[Test]
    public function itRetriesAFailedChunkBeforeReadingTheNextOne(): void
    {
        $output = $this->runFixture('fatal_settings_child.php');

        $this->assertLessThan(
            strpos($output, 'processed second row'),
            strpos($output, 'giving up on it')
        );
    }

    #[Test]
    public function itGivesUpAtOnceWhenNoRetriesAreAllowed(): void
    {
        $output = $this->runFixture('fatal_settings_child.php', ['MP_MAX_RETRIES' => '0']);

        $this->assertSame(1, substr_count($output, 'exited with an error'));
        $this->assertStringNotContainsString('to be retried', $output);
        $this->assertStringContainsString('failed 1 times, giving up on it', $output);
        $this->assertStringContainsString('Chunks handed to a child: 3', $output);
    }

    #[Test]
    public function itDropsAFailingChunkWithoutRetryingWhenRetryOnFatalIsOff(): void
    {
        $output = $this->runFixture('fatal_settings_child.php', ['MP_RETRY_ON_FATAL' => '0']);

        $this->assertSame(1, substr_count($output, 'exited with an error'));
        $this->assertStringNotContainsString('to be retried', $output);
        $this->assertStringNotContainsString('giving up on it', $output);
        $this->assertStringContainsString('processed second row', $output);
        $this->assertStringContainsString('MultiProcessor done!', $output);
        $this->assertStringContainsString('Chunks handed to a child: 3', $output);
    }

    #[Test]
    public function itAbortsTheWholeRunWhenExitOnFatalIsOn(): void
    {
        $output = $this->runFixture(
            'fatal_settings_child.php',
            ['MP_EXIT_ON_FATAL' => '1', 'MP_RETRY_ON_FATAL' => '0']
        );

        $this->assertStringContainsString('exited with an error', $output);
        $this->assertStringContainsString('MultiProcessor aborted successfully!', $output);
        $this->assertStringNotContainsString('processed second row', $output);
        $this->assertStringNotContainsString('MultiProcessor done!', $output);
    }

    /**
     * exitOnFatal is checked before retryOnFatal, so turning it on switches retrying off
     * whatever retryOnFatal says.
     */
    #[Test]
    public function itLetsExitOnFatalOverruleRetryOnFatal(): void
    {
        $output = $this->runFixture(
            'fatal_settings_child.php',
            ['MP_EXIT_ON_FATAL' => '1', 'MP_RETRY_ON_FATAL' => '1', 'MP_MAX_RETRIES' => '5']
        );

        $this->assertSame(1, substr_count($output, 'exited with an error'));
        $this->assertStringNotContainsString('to be retried', $output);
        $this->assertStringContainsString('MultiProcessor aborted successfully!', $output);
        $this->assertStringNotContainsString('MultiProcessor done!', $output);
    }

    /**
     * A run that aborts never reaches finish(), so nothing the consumer put there happens
     * and no summary is logged.
     */
    #[Test]
    public function itSkipsTheSummaryWhenItAborts(): void
    {
        $output = $this->runFixture('fatal_settings_child.php', ['MP_EXIT_ON_FATAL' => '1']);

        $this->assertStringNotContainsString('Total time spent', $output);
        $this->assertStringNotContainsString('Chunks handed to a child', $output);
    }
}
