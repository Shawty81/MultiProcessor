<?php

namespace MultiProcessor\Log;

use Psr\Log\AbstractLogger;

class CommandLineLogger extends AbstractLogger
{
    /**
     * @param $level
     * @param $message
     * @param mixed[] $context
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        foreach($context as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        printf(date('H:i:s') . ' [' . strtoupper(substr($level, 0, 1)) . ']  ' . $message . PHP_EOL);
    }

}
