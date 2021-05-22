<?php

namespace Queue\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Queue\Queue\TaskFinder;

class InfoCommand extends Command {

	/**
	 * @inheritDoc
	 */
	public static function defaultName(): string {
		return 'queue info';
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$parser = parent::getOptionParser();

		$parser->setDescription(
			'Get list of available tasks as well as current settings and statistics.'
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
	}

	/**
	 * @return string[]
	 */
	protected function getTasks(): array {
		$taskFinder = new TaskFinder();

		return $taskFinder->allAppAndPluginTasks();
	}

}
