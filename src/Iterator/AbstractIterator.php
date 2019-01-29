<?php

namespace Shawty\MultiProcessor\Iterator;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

abstract class AbstractIterator implements IteratorInterface {
	use LoggerAwareTrait;

	protected $chunkSize = 1;

	public function __construct() {
		$this->logger = new NullLogger();
	}

	public function setChunkSize($size) {
		$this->chunkSize = $size;
	}

}

