<?php

namespace Queue\Queue;

use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;

class TaskFinder {

	/**
	 * @var array|null
	 */
	protected $tasks;

	/**
	 * @return array
	 */
	public function allAppAndPluginTasks() {
		if ($this->tasks !== null) {
			return $this->tasks;
		}

		$paths = App::path('Shell/Task');
		$this->tasks = [];

		foreach ($paths as $path) {
			$Folder = new Folder($path);
			$res = array_merge($this->tasks, $Folder->find('Queue.+\.php'));
			foreach ($res as &$r) {
				$r = basename($r, 'Task.php');
			}
			$this->tasks = $res;
		}
		$plugins = Plugin::loaded();
		foreach ($plugins as $plugin) {
			$pluginPaths = App::path('Shell/Task', $plugin);
			foreach ($pluginPaths as $pluginPath) {
				$Folder = new Folder($pluginPath);
				$res = $Folder->find('Queue.+Task\.php');
				foreach ($res as &$r) {
					$r = $plugin . '.' . basename($r, 'Task.php');
				}
				$this->tasks = array_merge($this->tasks, $res);
			}
		}

		return $this->tasks;
	}

}
