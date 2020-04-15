<?php

namespace Queue\Generator\Task;

use Cake\Core\App;
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

		$models = $this->collectQueuedJobTasks();
		foreach ($models as $model => $className) {
			$list[$model] = '\\' . $className . '::class';
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
	protected function collectQueuedJobTasks() {
		$result = [];

		$taskFinder = new TaskFinder();
		$tasks = $taskFinder->allAppAndPluginTasks();
		sort($tasks);

		foreach ($tasks as $task) {
			$className = App::className($task, 'Shell/Task', 'Task');
			[, $task] = pluginSplit($task);
			$task = substr($task, 5);
			$result[$task] = $className;
		}

		return $result;
	}

}
