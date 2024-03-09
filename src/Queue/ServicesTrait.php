<?php
declare(strict_types=1);

namespace Queue\Queue;

use Cake\Core\ContainerInterface;
use LogicException;

trait ServicesTrait {

	/**
	 * @var \Cake\Core\ContainerInterface|null
	 */
	protected ?ContainerInterface $container = null;

	/**
	 * @template OBJECT
	 *
	 * @param class-string<OBJECT> $id Classname or identifier of the service you want to retrieve
	 *
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 *
	 * @return OBJECT
	 */
	protected function getService(string $id) {
		if ($this->container === null) {
			throw new LogicException(
				"The Container has not been set. Hint: getService() must not be called in the Task's constructor.",
			);
		}

		return $this->container->get($id);
	}

	/**
	 * @param \Cake\Core\ContainerInterface $container
	 *
	 * @return void
	 */
	public function setContainer(ContainerInterface $container): void {
		$this->container = $container;
	}

}
