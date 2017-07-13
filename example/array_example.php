<?php

require_once __DIR__ . '/autoloader.php';
require_once 'ChildProcessor/Example.php';

use MultiProcessor\MultiProcessor;
use MultiProcessor\Iterator\ArrayIterator;
use MultiProcessor\Log\CommandLineLogger;

$logger = new CommandLineLogger();

$iterator = new ArrayIterator();
$iterator->setArray([
	'I want',
	'randomly sized strings',
	'in this array.',
	'The length of these strings',
	'will be used',
	'to determine',
	'the amound of seconds',
	'a child',
	'is going to sleep()',
	'and pretend to be doing stuff.'
]);
$iterator->setChunkSize(1);

$childProcessor = new Example();
$childProcessor->setLogger($logger);

$multiProcessor = new MultiProcessor($iterator, $childProcessor);
$multiProcessor->setMaxChildren(5);
$multiProcessor->setLogger($logger);

$multiProcessor->run();

$logger->info('');
$logger->info('As you can see, this was way faster than the 83 seconds this would\'ve lasted when using a normal script.');
$logger->info('Try messing around with the chunkSize and maxChildren settings to see how much it affects the speed of the MultiProcessor.');

