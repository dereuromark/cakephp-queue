<?php
declare(strict_types=1);

namespace Queue\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;

/**
 * ConfigureHelper
 *
 * Fallback helper for reading configuration values when Shim plugin is not available.
 */
class ConfigureHelper extends Helper {

	/**
	 * Read a configuration value.
	 *
	 * @param string|null $key The key to read.
	 * @param mixed $default The default value to return if key doesn't exist.
	 *
	 * @return mixed
	 */
	public function read(?string $key = null, mixed $default = null): mixed {
		return Configure::read($key, $default);
	}

}
