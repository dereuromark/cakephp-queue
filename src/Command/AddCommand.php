<?php
declare(strict_types=1);

namespace Queue\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Queue\Console\Io;
use Queue\Queue\TaskFinder;

class AddCommand extends Command {

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
			foreach ($tasks as $task => $className) {
				$io->out(' - ' . $task);
			}

			return;
		}

		if (!array_key_exists($taskName, $tasks)) {
			$io->abort('Not a supported task.');
		}

		/** @var class-string<\Queue\Queue\AddInterface> $taskClass */
		$taskClass = $tasks[$taskName];
		/** @var \Queue\Queue\AddInterface $task */
		$task = new $taskClass(new Io($io));
		$task->add($args->getArgument('data'));
	}

	/**
	 * @return array<string>
	 */
	protected function getTasks(): array {
		$taskFinder = new TaskFinder();

		return $taskFinder->allAddable();
	}

}
