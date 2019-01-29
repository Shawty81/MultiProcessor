<?php

namespace Shawty\MultiProcessor\Iterator;

interface IteratorInterface {

	public function setChunkSize($size);
	public function init();
	public function finish();
	public function getChunk(): array;
	public function getNumberOfChunks(): int;
	public function hasConnections(): bool;

}

