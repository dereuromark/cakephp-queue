<?php

namespace Queue;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Queue\Command\AddCommand;
use Queue\Command\BakeQueueTaskCommand;
use Queue\Command\InfoCommand;
use Queue\Command\JobCommand;
use Queue\Command\MigrateTasksCommand;
use Queue\Command\RunCommand;
use Queue\Command\WorkerCommand;

/**
 * Plugin for Queue
 */
class Plugin extends BasePlugin {

	/**
	 * @var bool
	 */
	protected $middlewareEnabled = false;

	/**
	 * Console hook
	 *
	 * @param \Cake\Console\CommandCollection $commands The command collection
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
		if (Configure::read('debug')) {
			$commands->add('queue migrate_tasks', MigrateTasksCommand::class);
		}

		return $commands;
	}

}
