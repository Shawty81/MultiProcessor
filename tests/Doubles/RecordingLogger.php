<?php

declare(strict_types=1);

namespace MultiProcessor\Tests\Doubles;

use Override;
use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Keeps every call as it was made, so a test can assert on the value behind a placeholder
 * instead of on the rendered line a particular logger happens to produce.
 */
final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{message: string, context: mixed[]}> */
    private array $records = [];

    /**
     * @param mixed $level
     * @param string|Stringable $message
     * @param mixed[] $context
     */
    #[Override]
    public function log($level, $message, array $context = []): void
    {
        $this->records[] = ['message' => (string) $message, 'context' => $context];
    }

    /**
     * Returns the value the given placeholder carried the last time the given message was
     * logged, or null when the message was never logged with a value that has a string form.
     */
    public function lastValueFor(string $message, string $placeholder): ?string
    {
        foreach (array_reverse($this->records) as $record) {
            if ($record['message'] !== $message) {
                continue;
            }

            $value = $record['context'][$placeholder] ?? null;

            return is_scalar($value) ? (string) $value : null;
        }

        return null;
    }
}
