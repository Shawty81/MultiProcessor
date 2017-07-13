<?php

use MultiProcessor\ChildProcessor\AbstractChildProcessor;

class Example extends AbstractChildProcessor {

	public function init() {
		
	}

	public function process(array $chunk) {
		foreach($chunk as $row) {
			$seconds = floor(strlen($row) / 2);
			
			$this->logger->info('Hi I\'m pid: {pid}! And i\'m pretending to do some queries and other stuff for: {seconds} seconds', ['pid' => getmypid(), 'seconds' => $seconds]);
			
			sleep($seconds);
		}

		$this->logger->info('I\'m pid: {pid}! And i\'m done now!!', ['pid' => getmypid()]);
	}

	public function finish() {
	
	}

}

