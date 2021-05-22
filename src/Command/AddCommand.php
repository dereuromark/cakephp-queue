<?php

namespace Queue\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
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
		$parser->setDescription(
			'Adds a job into the queue. Only tasks that implement AddInterface can be added through CLI.'
		);

		return $parser;
	}

	/**
	 * @param \Cake\Console\Arguments $args Arguments
	 * @param \Cake\Console\ConsoleIo $io ConsoleIo
	 * @return int|null|void
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$tasks = $this->getTasks();

		$task = $args->getArgument('task');
		if (!$task) {
			$task = $io->ask('Task');
		}
		if (!in_array($task, $tasks)) {
			$io->abort('Not a supported task.');
		}

		//TODO
	}

	/**
	 * @return string[]
	 */
	protected function getTasks(): array {
		$taskFinder = new TaskFinder();

		return $taskFinder->allAppAndPluginTasks();
	}

}
