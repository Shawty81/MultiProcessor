<?php

namespace MultiProcessor\Log;

use Psr\Log\AbstractLogger;
use Stringable;

final class CommandLineLogger extends AbstractLogger
{
    /**
     * @param mixed $level
     * @param string|Stringable $message
     * @param mixed[] $context
     */
    public function log($level, $message, array $context = []): void
    {
        echo date('H:i:s')
            . ' [' . strtoupper(substr($this->stringify($level) ?? '?', 0, 1)) . ']  '
            . $this->interpolate((string) $message, $context)
            . PHP_EOL;
    }

    /**
     * Replaces the PSR-3 placeholders with the context values that have a string form.
     * Values that have none are left alone, placeholder and all, because dropping the
     * placeholder would hide that the caller meant to say something there.
     *
     * @param mixed[] $context
     */
    private function interpolate(string $message, array $context): string
    {
        $replacements = [];

        foreach ($context as $placeholder => $value) {
            $replacement = $this->stringify($value);

            if ($replacement !== null) {
                $replacements['{' . $placeholder . '}'] = $replacement;
            }
        }

        return strtr($message, $replacements);
    }

    private function stringify(mixed $value): ?string
    {
        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return null;
    }
}
