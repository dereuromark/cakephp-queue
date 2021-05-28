<?php

namespace Queue\Queue;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Queue\Queue\Task\AddInterface;

class TaskFinder {

	/**
	 * @phpstan-var array<string, class-string<\Queue\Queue\Task\Task>>|null
	 *
	 * @var string[]|null
	 */
	protected $tasks;

	/**
	 * @phpstan-return array<string, class-string<\Queue\Queue\Task\Task>>
	 *
	 * @return string[]
	 */
	public function allAddable(): array {
		$all = $this->all();
		foreach ($all as $task => $class) {
			if (!is_subclass_of($class, AddInterface::class, true)) {
				unset($all[$task]);
			}
		}

		return $all;
	}

	/**
	 * Returns all possible Queue tasks.
	 *
	 * Makes sure that app tasks are prioritized over plugin ones.
	 *
	 * @phpstan-return array<string, class-string<\Queue\Queue\Task\Task>>
	 *
	 * @return string[]
	 */
	public function all(): array {
		if ($this->tasks !== null) {
			return $this->tasks;
		}

		$paths = App::classPath('Queue/Task');
		$this->tasks = [];

		foreach ($paths as $path) {
			$this->tasks += $this->getTasks($path);
		}
		$plugins = array_merge((array)Configure::read('Queue.plugins'), Plugin::loaded());
		$plugins = array_unique($plugins);
		foreach ($plugins as $plugin) {
			$pluginPaths = App::classPath('Queue/Task', $plugin);
			foreach ($pluginPaths as $pluginPath) {
				$pluginTasks = $this->getTasks($pluginPath, $plugin);
				$this->tasks += $pluginTasks;
			}
		}

		return $this->tasks;
	}

	/**
	 * @phpstan-return array<string, class-string<\Queue\Queue\Task\Task>>
	 *
	 * @param string $path
	 * @param string|null $plugin
	 *
	 * @return string[]
	 */
	protected function getTasks(string $path, ?string $plugin = null): array {
		$Folder = new Folder($path);

		$tasks = [];
		$files = $Folder->find('.+Task\.php');
		foreach ($files as $file) {
			$name = basename($file, 'Task.php');
			$namespace = $plugin ? str_replace('/', '\\', $plugin) : Configure::read('App.namespace');

			/** @phpstan-var class-string<\Queue\Queue\Task\Task> $className */
			$className = $namespace . '\Queue\Task\\' . $name . 'Task';
			$key = $plugin ? $plugin . '.' . $name : $name;
			$tasks[$key] = $className;
		}

		return $tasks;
	}

}
