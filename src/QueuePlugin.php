<?php
declare(strict_types=1);

namespace Queue;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\ContainerInterface;
use Cake\Routing\RouteBuilder;
use Queue\Command\AddCommand;
use Queue\Command\BakeQueueTaskCommand;
use Queue\Command\InfoCommand;
use Queue\Command\JobCommand;
use Queue\Command\RunCommand;
use Queue\Command\WorkerCommand;

/**
 * Plugin for Queue
 */
class QueuePlugin extends BasePlugin {

	/**
	 * @var bool
	 */
	protected bool $middlewareEnabled = false;

	/**
	 * Console hook
	 *
	 * @param \Cake\Console\CommandCollection $commands The command collection
	 *
	 * @return \Cake\Console\CommandCollection
	 */
	public function console(CommandCollection $commands): CommandCollection {
		$commands->add('queue add', AddCommand::class);
		$commands->add('queue info', InfoCommand::class);
		$commands->add('queue run', RunCommand::class);
		$commands->add('queue worker', WorkerCommand::class);
		$commands->add('queue job', JobCommand::class);
		if (class_exists('Bake\Command\SimpleBakeCommand')) {
			$commands->add('bake queue_task', BakeQueueTaskCommand::class);
		}

		return $commands;
	}

	/**
	 * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
	 *
	 * @return void
	 */
	public function routes(RouteBuilder $routes): void {
		$routes->prefix('Admin', function (RouteBuilder $routes): void {
			$routes->plugin('Queue', function (RouteBuilder $routes): void {
				$routes->connect('/', ['controller' => 'Queue', 'action' => 'index']);

				$routes->fallbacks();
			});
		});

		$routes->plugin('Queue', ['path' => '/queue'], function (RouteBuilder $routes): void {
			$routes->connect('/{controller}');
		});
	}

	/**
	 * @param \Cake\Core\ContainerInterface $container The DI container instance
	 *
	 * @return void
	 */
	public function services(ContainerInterface $container): void {
		$container->add(ContainerInterface::class, $container);
		$container
			->add(RunCommand::class)
			->addArgument(ContainerInterface::class);
	}

}
