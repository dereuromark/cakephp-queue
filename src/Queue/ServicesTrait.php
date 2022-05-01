<?php

namespace Queue\Queue;

use Cake\Core\ContainerInterface;

trait ServicesTrait {

	/**
	 * Overwrite this method inside your task to get access to the DI container
	 *
	 * @param \Cake\Core\ContainerInterface $container
	 * @return void
	 */
	public function services(ContainerInterface $container): void {
	}

}
