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
	protected bool $middlewareEnabled = false;

	/**
	 * @var bool
	 */
	protected bool $consoleEnabled = false;

	/**
	 * @var bool
	 */
	protected bool $bootstrapEnabled = false;

	/**
	 * @var bool
	 */
	protected bool $routesEnabled = false;

	/**
	 * @var bool
	 */
	protected bool $servicesEnabled = false;

}
