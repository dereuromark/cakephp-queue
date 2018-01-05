<?php
namespace Queue\Generator\Task;

use Cake\Core\App;
use IdeHelper\Generator\Task\TaskInterface;
use Queue\Queue\TaskFinder;

class QueuedJobTask implements TaskInterface {

	/**
	 * @var array
	 */
	protected $aliases = [
		'\Queue\Model\Table\QueuedJobsTable::createJob(0)',
	];

	/**
	 * @return array
	 */
	public function collect() {
		$map = [];

		$models = $this->collectQueuedJobTasks();
		foreach ($models as $model => $className) {
			$map[$model] = '\\' . $className . '::class';
		}

		$result = [];
		foreach ($this->aliases as $alias) {
			$result[$alias] = $map;
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
