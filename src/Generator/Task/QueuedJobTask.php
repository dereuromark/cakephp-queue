<?php
declare(strict_types=1);

namespace Queue\Generator\Task;

use IdeHelper\Generator\Directive\ExpectedArguments;
use IdeHelper\Generator\Task\TaskInterface;
use Queue\Queue\TaskFinder;

class QueuedJobTask implements TaskInterface {

	/**
	 * @var array<int>
	 */
	protected array $aliases = [
		'\Queue\Model\Table\QueuedJobsTable::createJob()' => 0,
		'\Queue\Model\Table\QueuedJobsTable::isQueued()' => 1,
	];

	/**
	 * @return array<\IdeHelper\Generator\Directive\BaseDirective>
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
	 * @return array<string>
	 */
	protected function collectQueuedJobTasks(): array {
		$taskFinder = new TaskFinder();
		$tasks = $taskFinder->all();

		return $tasks;
	}

}
