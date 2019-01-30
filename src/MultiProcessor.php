<?php

namespace Shawty\MultiProcessor;

use \DateTime;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class MultiProcessor {
	use LoggerAwareTrait;

	protected $iterator;
	protected $childProcessor;
	
	protected $maxChildren;

	protected $childCounter = 0;
	protected $totalChunks = false;
	protected $parentPid = 0;

	public function __construct(Iterator\AbstractIterator $iterator, ChildProcessor\AbstractChildProcessor $childProcessor) {
		$this->iterator = $iterator;
		$this->childProcessor = $childProcessor;
		$this->logger = new NullLogger();

		$this->parentPid = getmypid();
	}

	public function setMaxChildren($maxChildren) {
		$this->maxChildren = $maxChildren;
	}

	public function run() {
		$this->init();
		
		$this->totalChunks = $this->iterator->getNumberOfChunks();

		$this->startProcessing();

		$this->finish();
	}

	private function init() {
		if(!isset($this->maxChildren)) {
			throw new Exception('Please call MultiProcessor::setMaxChildren(int) before calling MultiProcessor::run()');
		}

		$this->startTime = time();
		$this->logger->info('Starting MultiProcessor');
		$this->logger->info('');

		$this->childProcessor->init();
		$this->iterator->init();
	}

	private function startProcessing() {
		declare(ticks = 1) {
			while(1) {
				$chunk = $this->iterator->getChunk();

				// If the chunk is empty it means the script is almost done
				if(empty($chunk)) {
					// Wait for all children to exit before breaking the while loop
					while($this->childCounter > 0) {
						$this->waitOnChildtoExit();
					}

					break;
				}

				$pid = $this->fork();

				if($pid == -1) {
					// Something is very wrong
					die('could not fork');
				}
				else if($pid) { 
					// This is the parent

					$this->childCounter++;

					// If number of children is equal or bigger than maxChildren. Wait for a child to exit
					if($this->childCounter >= $this->maxChildren) {
						$this->waitOnChildtoExit();
					}
				}
				else { 
					// This is a child
					
					// if there is no chunk, exit the process
					if(empty($chunk)) exit(0);

                    // If your iterator and ChildProcessor use the same persistent connections some external form of storage (for example MySQL), this is the moment to drop those connections
                    if($this->iterator->hasConnections()) {
                        $this->iterator->dropConnections();
                    }

					// Do whatever needs to be done
					$this->childProcessor->process($chunk);

					// Child process is done, exit
					exit(0);
				}
			}
		}
	}

	private function fork() {
		return pcntl_fork();
	}


	private function waitOnChildtoExit() {
		// Waits for a child to stop
		$childPid = pcntl_waitpid(0, $status);

		// child exited
		if(pcntl_wifexited($status)) {
			// Check the exit status
			switch(pcntl_wexitstatus($status)) {
				case 1:
					// exited because there is no chunk
				case 0:
					// child exited correctly
					break;
				case 255:
					// Child fataled. For now we are not going to do anything with this
					break;
				default:
					$this->logger->info('Child exited with unknown status [ ' . pcntl_wexitstatus($status) . ' ]');
					exit();
				break;
			}
		}

		// A Child is done, continueing script, update progressBar (to be implemented) and remove 1 child
		$this->childCounter--;
	}

	private function finish() {
		$this->childProcessor->finish();
		$this->iterator->finish();

		$this->endTime = time();

		$dateTimeFrom = new DateTime('@' . $this->startTime);
		$dateTimeTill = new DateTime('@' . $this->endTime);
	
		$time = $dateTimeFrom->diff($dateTimeTill)->format('%h hours, %i minutes and %s seconds');
		
		$this->logger->info('');

		$this->logger->info('MultiProcessor done!');
	
		$this->logger->info('Total time spent: {time}', ['time' => $time]);
	}

}

