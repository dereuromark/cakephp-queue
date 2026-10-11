<?php
declare(strict_types=1);

namespace Queue\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;
use Queue\Console\Io;
use Queue\Queue\Config;
use Queue\Queue\TaskFinder;

class AddCommand extends Command {

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Add a job to the queue.';
	}

	/**
	 * @inheritDoc
	 */
	public static function defaultName(): string {
		return 'queue add';
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$parser = parent::getOptionParser();

		$parser->addArgument('task', [
			'help' => 'Task name',
			'required' => false,
		]);
		$parser->addArgument('data', [
			'help' => 'Additional data if needed',
			'required' => false,
		]);
		$parser->addOption('connection', [
			'help' => 'Database connection to use (must be in Queue.connections whitelist if multi-connection mode is enabled)',
			'default' => null,
		]);
		$parser->setDescription(
			'Adds a job into the queue. Only tasks that implement AddInterface can be added through CLI.',
		);

		return $parser;
	}

	/**
	 * @param \Cake\Console\Arguments $args Arguments
	 * @param \Cake\Console\ConsoleIo $io ConsoleIo
	 *
	 * @return int|null|void
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$tasks = $this->getTasks();

		$taskName = $args->getArgument('task');
		if (!$taskName) {
			$io->out(count($tasks) . ' tasks available:');
			foreach (array_keys($tasks) as $task) {
				$io->out(' - ' . $task);
			}

			return;
		}

		if (!array_key_exists($taskName, $tasks)) {
			$io->abort('Not a supported task.');
		}

		$requestedConnection = $args->getOption('connection');
		/** @var class-string<\Queue\Queue\Task&\Queue\Queue\AddInterface> $taskClass */
		$taskClass = $tasks[$taskName];
		/** @var \Queue\Queue\Task&\Queue\Queue\AddInterface $task */
		$task = new $taskClass(new Io($io));
		$previousConnection = $task->QueuedJobs->getConnection();
		$connection = Config::resolveConnection(is_string($requestedConnection) ? $requestedConnection : null, $previousConnection->configName());
		/** @var \Cake\Database\Connection $connectionObject */
		$connectionObject = ConnectionManager::get($connection);
		$task->QueuedJobs->setConnection($connectionObject);
		try {
			$task->add($args->getArgument('data'));
		} finally {
			$task->QueuedJobs->setConnection($previousConnection);
		}
	}

	/**
	 * @return array<string>
	 */
	protected function getTasks(): array {
		$taskFinder = new TaskFinder();

		return $taskFinder->allAddable();
	}

}
