<?php

namespace Foo;

use Cake\Core\BasePlugin;

/**
 * Plugin for Queue
 */
class Plugin extends BasePlugin {

	/**
	 * @var bool
	 */
	protected $middlewareEnabled = false;

	/**
	 * @var bool
	 */
	protected $consoleEnabled = false;

	/**
	 * @var bool
	 */
	protected $bootstrapEnabled = false;

	/**
	 * @var bool
	 */
	protected $routesEnabled = false;

	/**
	 * @var bool
	 */
	protected $servicesEnabled = false;

}
