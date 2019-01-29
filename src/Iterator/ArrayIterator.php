<?php

namespace MultiProcessor\Iterator;

class ArrayIterator extends AbstractIterator {

	private $array = [];
	private $position;

	public function init() {
		$this->position = 0;
	}
	
	public function getChunk(): array {
		$chunk = [];
		
		while(count($chunk) < $this->chunkSize) {
			if(isset($this->array[$this->position])) {
				$chunk[] = $this->array[$this->position++];
			}
			else {
				break;
			}
		}

		return $chunk;
	}

	public function setArray(array $array) {
		$this->array = $array;
	}

	public function getNumberOfChunks(): int {
		return ceil(count($this->array) / $this->chunkSize);
	}

	public function hasConnections(): bool {
		return false;
	}

	public function finish() {

	}

}

