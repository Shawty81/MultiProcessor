<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MultiProcessor\ChildProcessor\AbstractChildProcessor;
use MultiProcessor\Iterator\ArrayIterator;
use MultiProcessor\Log\CommandLineLogger;
use MultiProcessor\MultiProcessor;

class Processor extends AbstractChildProcessor
{
    public function init(): void {}

    public function process(array $chunk): void
    {
        foreach($chunk as $row) {
            $seconds = floor(strlen($row) / 2);

            $this->logger->info(
                'Hi I\'m pid: {pid}! And i\'m pretending to do some queries and other stuff for: {seconds} seconds',
                ['pid' => getmypid(), 'seconds' => $seconds]
            );

            sleep($seconds);
        }

        $this->logger->info('I\'m pid: {pid}! And i\'m done now!!', ['pid' => getmypid()]);
    }

    public function finish(): void {}

}

$iterator = new ArrayIterator();
$iterator->setArray([
    'I want',
    'randomly sized strings',
    'in this array.',
    'The length of these strings',
    'will be used',
    'to determine',
    'the amount of seconds',
    'a child',
    'is going to sleep()',
    'and pretend to be doing stuff.',
]);
$iterator->setChunkSize(1);

$logger = new CommandLineLogger();

$childProcessor = new Processor();
$childProcessor->setLogger($logger);

$multiProcessor = new MultiProcessor($iterator, $childProcessor, 10);
$multiProcessor->setLogger($logger);

$multiProcessor->run();

$logger->info('');
$logger->info('As you can see, this was way faster than the 83 seconds this would\'ve lasted when using a normal script.');
$logger->info('Try messing around with the chunkSize and maxChildren settings to see how much it affects the speed of the MultiProcessor.');
