<?php
declare(strict_types=1);

namespace Queue\Migration;

use Cake\Core\App;

class OldTaskFinder {

	/**
	 * Returns all possible Queue tasks.
	 *
	 * Makes sure that app tasks are prioritized over plugin ones.
	 *
	 * @param string|null $plugin
	 *
	 * @return array<string>
	 */
	public function all(?string $plugin): array {
		$paths = App::classPath('Shell/Task', $plugin);

		$allTasks = [];
		foreach ($paths as $path) {
			$tasks = $this->getTasks($path, $plugin);

			$allTasks += $tasks;
		}

		return $allTasks;
	}

	/**
	 * @param string $path
	 * @param string|null $plugin
	 *
	 * @return array<string>
	 */
	protected function getTasks(string $path, ?string $plugin): array {
		$res = glob($path . '*Task.php') ?: [];

		$tasks = [];
		foreach ($res as $r) {
			$name = basename($r, 'Task.php');
			$name = substr($name, 5);

			$taskKey = $plugin ? $plugin . '.' . $name : $name;
			$tasks[$taskKey] = $path . basename($r);
		}

		return $tasks;
	}

}
