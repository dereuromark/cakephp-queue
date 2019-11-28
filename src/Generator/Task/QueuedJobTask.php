<?php

namespace Queue\Generator\Task;

use Cake\Core\App;
use IdeHelper\Generator\Directive\Override;
use IdeHelper\Generator\Task\TaskInterface;
use Queue\Queue\TaskFinder;

class QueuedJobTask implements TaskInterface {

	/**
	 * @var string[]
	 */
	protected $aliases = [
		'\Queue\Model\Table\QueuedJobsTable::createJob(0)',
	];

	/**
	 * @return \IdeHelper\Generator\Directive\BaseDirective[]
	 */
	public function collect() {
		$map = [];

		$models = $this->collectQueuedJobTasks();
		foreach ($models as $model => $className) {
			$map[$model] = '\\' . $className . '::class';
		}

		ksort($map);

		$result = [];
		foreach ($this->aliases as $alias) {
			$directive = new Override($alias, $map);
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
			list(, $task) = pluginSplit($task);
			$task = substr($task, 5);
			$result[$task] = $className;
		}

		return $result;
	}

}
