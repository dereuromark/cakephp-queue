<?php

namespace Queue\Generator\Task;

use IdeHelper\Generator\Directive\ExpectedArguments;
use IdeHelper\Generator\Task\TaskInterface;
use Queue\Queue\TaskFinder;

class QueuedJobTask implements TaskInterface {

	/**
	 * @var int[]
	 */
	protected $aliases = [
		'\Queue\Model\Table\QueuedJobsTable::createJob()' => 0,
		'\Queue\Model\Table\QueuedJobsTable::isQueued()' => 1,
	];

	/**
	 * @return \IdeHelper\Generator\Directive\BaseDirective[]
	 */
	public function collect(): array {
		$list = [];

		$names = $this->collectQueuedJobTasks();
		foreach ($names as $name => $className) {
			$list[$name] = "'$name'";
		}

		ksort($list);

		$result = [];
		foreach ($this->aliases as $alias => $position) {
			$directive = new ExpectedArguments($alias, $position, $list);
			$result[$directive->key()] = $directive;
		}

		return $result;
	}

	/**
	 * @return string[]
	 */
	protected function collectQueuedJobTasks(): array {
		$taskFinder = new TaskFinder();
		$tasks = $taskFinder->all();

		return $tasks;
	}

}
