<?php

namespace Shawty\MultiProcessor\ChildProcessor;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

abstract class AbstractChildProcessor implements ChildProcessorInterface {
	use LoggerAwareTrait;

	public function __constuct() {
		$this->logger = new NullLogger();
	}

}

