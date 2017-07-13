<?php

/** 
 * I want to add the MultiProcessor as a composer package, but for now I just wanted to commit this project to github. 
 *
 * To make this example work you'll need to have the psr\log composer package and add the path to the composer autoloader in $pathToVendor
 */
$pathToVendor = __DIR__ . '/../../../vendor/';

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

