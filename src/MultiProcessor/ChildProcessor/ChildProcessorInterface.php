<?php

namespace MultiProcessor\ChildProcessor;

interface ChildProcessorInterface {

	public function init();
	public function process(array $chunk);
	public function finish();

}

