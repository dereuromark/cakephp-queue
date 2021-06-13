<?php

namespace Queue\Queue;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use InvalidArgumentException;

class TaskFinder {

	/**
	 * @phpstan-var array<string, class-string<\Queue\Queue\Task>>|null
	 *
	 * @var string[]|null
	 */
	protected $tasks;

	/**
	 * @phpstan-return array<string, class-string<\Queue\Queue\Task>>
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
	 * @phpstan-return array<string, class-string<\Queue\Queue\Task>>
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
	 * @phpstan-return array<string, class-string<\Queue\Queue\Task>>
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

			/** @phpstan-var class-string<\Queue\Queue\Task> $className */
			$className = $namespace . '\Queue\Task\\' . $name . 'Task';
			$key = $plugin ? $plugin . '.' . $name : $name;
			$tasks[$key] = $className;
		}

		return $tasks;
	}

	/**
	 * Resolves FQCN to a task name.
	 *
	 * @param string $jobType
	 *
	 * @return string
	 */
	public function resolve(string $jobType): string {
		$all = $this->all();
		foreach ($all as $name => $className) {
			if ($jobType === $className || $jobType === $name) {
				return $name;
			}
		}

		if (strpos($jobType, '\\') === false) {
			// Let's try matching without plugin prefix
			foreach ($all as $name => $className) {
				if (strpos($name, '.') === false) {
					continue;
				}
				[$plugin, $name] = explode('.', $name, 2);
				if ($jobType === $name) {
					$message = 'You seem to be adding a plugin job without plugin syntax (' . $jobType . '), migrate to using ' . $plugin . '.' . $name . ' instead.';
					trigger_error($message, E_USER_DEPRECATED);

					return $plugin . '.' . $name;
				}
			}
		}

		throw new InvalidArgumentException('No job type can be resolved for ' . $jobType);
	}

}
