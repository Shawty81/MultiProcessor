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
        $message = (string) $message;

        foreach ($context as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        printf(date('H:i:s') . ' [' . strtoupper(substr($level, 0, 1)) . ']  ' . $message . PHP_EOL);
    }
}
