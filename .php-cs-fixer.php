<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true,
        '@PHP84Migration' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/tmp/.php-cs-fixer.cache');
