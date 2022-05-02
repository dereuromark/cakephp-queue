<?php

namespace Queue\Queue;

use Cake\Core\ContainerInterface;

trait ServicesTrait {

	/**
	 * @var \Cake\Core\ContainerInterface
	 */
	protected $container;

	/**
	 * @param string $id Classname or identifier of the service you want to retrieve
	 * @return mixed
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 * @throws  \Psr\Container\ContainerExceptionInterface
	 */
	public function getService(string $id) {
		return $this->container->get($id);
	}

	/**
	 * @param \Cake\Core\ContainerInterface $container
	 * @return void
	 */
	public function setContainer(ContainerInterface $container) {
		$this->container = $container;
	}

}
