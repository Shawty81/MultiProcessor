<?php

namespace Shawty\MultiProcessor\Log;

use Psr\Log\AbstractLogger;

class CommandLineLogger extends AbstractLogger  {

	public function log($level, $message, Array $context = array()) {
		foreach($context as $key => $value) {
			$message = str_replace('{' . $key . '}', $value, $message);
		}

		printf(date('H:i:s') . ' [' . strtoupper(substr($level, 0, 1)) . ']  ' . $message . PHP_EOL);
	}

}

