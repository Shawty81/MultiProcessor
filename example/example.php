<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\Iterator\ArrayIterator;
use MultiProcessor\Log\CommandLineLogger;
use MultiProcessor\MultiProcessor;
use MultiProcessor\Queue\Chunk;
use MultiProcessor\Settings;
use Psr\Log\LoggerAwareTrait;

class Processor implements ChildProcessorInterface
{
    use LoggerAwareTrait;

    public function init(): void {}

    public function process(Chunk $chunk): void
    {
        foreach($chunk->data as $row) {
            $seconds = floor(strlen($row) / 2);

            $this->logger->info(
                'Hi I\'m pid: {pid}! And i\'m pretending to do some queries and other stuff for: {seconds} seconds',
                ['pid' => getmypid(), 'seconds' => $seconds]
            );

            // There is an 80% chance this child fails halfway through processing
            $error = mt_rand(0, 10) > 8;
            if ($error) {
                sleep((int) floor($seconds / 2));
                throw new Exception('test');
            }

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

$logger = new CommandLineLogger();

$childProcessor = new Processor();
$childProcessor->setLogger($logger);

$settings = (new Settings())
    ->setIterator($iterator)
    ->setChildProcessor($childProcessor)
    ->setLogger($logger)
    ->setChunkSize(1)
    ->setMaxChildren(5)
;

$multiProcessor = new MultiProcessor($settings);

$multiProcessor->run();

$logger->info('');
$logger->info('As you can see, this was way faster than the 83 seconds this would\'ve lasted when using a normal script.');
$logger->info('Try messing around with the chunkSize and maxChildren settings to see how much it affects the speed of the MultiProcessor.');
