<?php

$pathToVendor = __DIR__ . '/../../../';

require_once $pathToVendor . 'autoload.php';

class AutoLoader {

	public function register() {
		spl_autoload_register(array($this, 'load'));
	}

	public function load($class) {
		$location = __DIR__ . '/../src' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

		if(!file_exists($location)) {
			return false;
		}
		
		require_once $location;
	}

}

$autoloader = new AutoLoader();
$autoloader->register();

