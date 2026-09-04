<?php

declare(strict_types=1);

namespace MultiProcessor\Tests;

/**
 * Runs one of the fixture scripts in its own process, the way a consumer would run their
 * own script, and hands back everything it wrote to the terminal.
 */
trait FixtureRunner
{
    /**
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
