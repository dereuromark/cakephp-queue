<?php

namespace Queue\Queue;

use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;

class TaskFinder {

	/**
	 * @var string[]|null
	 */
	protected $tasks;

	/**
	 * Returns all possible Queue tasks.
	 *
	 * Makes sure that app tasks are prioritized over plugin ones.
	 *
	 * @return string[]
	 */
	public function allAppAndPluginTasks() {
		if ($this->tasks !== null) {
			return $this->tasks;
		}

		$paths = App::classPath('Shell/Task');
		$this->tasks = [];

		foreach ($paths as $path) {
			$Folder = new Folder($path);
			$this->tasks = $this->getAppPaths($Folder);
		}
		$plugins = Plugin::loaded();
		foreach ($plugins as $plugin) {
			$pluginPaths = App::classPath('Shell/Task', $plugin);
			foreach ($pluginPaths as $pluginPath) {
				$Folder = new Folder($pluginPath);
				$pluginTasks = $this->getPluginPaths($Folder, $plugin);
				$this->tasks = array_merge($this->tasks, $pluginTasks);
			}
		}

		return $this->tasks;
	}

	/**
	 * @param \Cake\Filesystem\Folder $Folder
	 *
	 * @return string[]
	 */
	protected function getAppPaths(Folder $Folder) {
		$res = array_merge($this->tasks, $Folder->find('Queue.+\.php'));
		foreach ($res as &$r) {
			$r = basename($r, 'Task.php');
		}

		return $res;
	}

	/**
	 * @param \Cake\Filesystem\Folder $Folder
	 * @param string $plugin
	 *
	 * @return string[]
	 */
	protected function getPluginPaths(Folder $Folder, $plugin) {
		$res = $Folder->find('Queue.+Task\.php');
		foreach ($res as $key => $r) {
			$name = basename($r, 'Task.php');
			if (in_array($name, $this->tasks)) {
				unset($res[$key]);
				continue;
			}
			$res[$key] = $plugin . '.' . $name;
		}

		return $res;
	}

}
