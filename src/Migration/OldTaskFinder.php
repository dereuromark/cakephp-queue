<?php

namespace Queue\Migration;

use Cake\Core\App;
use Cake\Filesystem\Folder;

class OldTaskFinder {

	/**
	 * Returns all possible Queue tasks.
	 *
	 * Makes sure that app tasks are prioritized over plugin ones.
	 *
	 * @param string|null $plugin
	 *
	 * @return string[]
	 */
	public function all(?string $plugin) {
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
	 * @return string[]
	 */
	protected function getTasks(string $path, ?string $plugin) {
		$Folder = new Folder($path);
		$res = $Folder->find('Queue.+Task\.php');

		$tasks = [];
		foreach ($res as $key => $r) {
			$name = basename($r, 'Task.php');
			$name = substr($name, 5);

			$taskKey = $plugin ? $plugin . '.' . $name : $name;
			$tasks[$taskKey] = $path . $r;
		}

		return $tasks;
	}

}
