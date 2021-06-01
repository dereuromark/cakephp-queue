<?php

namespace Queue\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Queue\Migration\OldTaskFinder;
use RuntimeException;

/**
 * For local migration/development only. Do not use/deploy on prod.
 */
class MigrateTasksCommand extends Command {

	/**
	 * @inheritDoc
	 */
	public static function defaultName(): string {
		return 'queue migrate_tasks';
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$parser = parent::getOptionParser();

		$parser->addOption('plugin', [
			'help' => 'Plugin name',
			'short' => 'p',
		]);
		$parser->addOption('remove', [
			'help' => 'Remove shell task class afterwards (instead of just copying)',
			'short' => 'r',
			'boolean' => true,
		]);
		$parser->addOption('overwrite', [
			'help' => 'Overwrite existing class file',
			'short' => 'o',
			'boolean' => true,
		]);
		$parser->setDescription(
			'Only needed for upgrade of old Shell tasks to new Queue tasks.' . PHP_EOL
			. ' - Shell/Task/Queue.+Task => Queue/Task/.+Task' . PHP_EOL
			. ' - IO access refactor'
		);

		return $parser;
	}

	/**
	 * @param \Cake\Console\Arguments $args Arguments
	 * @param \Cake\Console\ConsoleIo $io ConsoleIo
	 * @return int|null|void
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$tasks = $this->getTasks($args->getOption('plugin') ? (string)$args->getOption('plugin') : null);
		if (!$tasks) {
			$io->abort('Nothing to migrate.');
		}

		$io->out(count($tasks) . ' shell tasks to migrate...');

		foreach ($tasks as $task => $fileName) {
			$plugin = null;
			if (strpos($task, '.') !== false) {
				[$plugin, $task] = pluginSplit($task);
			}

			$path = $plugin ? Plugin::classPath($plugin) : APP;
			$path .= 'Queue' . DS . 'Task' . DS;

			$newTaskName = $task . 'Task';
			$newTaskFileName = $path . $newTaskName . '.php';
			if (!$args->getOption('overwrite') && file_exists($newTaskFileName)) {
				$io->warning($newTaskName . ' already exists, skipping!');

				continue;
			}

			$this->migrateTask($task, $plugin, $fileName, $newTaskFileName);

			$message = $newTaskName . ' created in ' . str_replace(ROOT . DS, '', $path);
			if ($args->getOption('remove')) {
				unlink($fileName);
				$message .= ', old class removed';
			}
			$io->success($message);
		}

		foreach ($tasks as $task => $fileName) {
			$plugin = null;
			if (strpos($task, '.') !== false) {
				[$plugin, $task] = pluginSplit($task);
			}

			$path = $plugin ? Plugin::path($plugin) . 'tests' . DS . 'TestCase' . DS : 'tests' . DS . 'TestCase' . DS;
			$newPath = $path . 'Queue' . DS . 'Task' . DS;

			$oldPath = $path . 'Shell' . DS . 'Task' . DS;
			$testFileName = 'Queue' . $task . 'TaskTest.php';
			if (!file_exists($oldPath . $testFileName)) {
				continue;
			}

			$newTestFileName = $task . 'TaskTest.php';
			$this->migrateTaskTest($task, $plugin, $oldPath . $testFileName, $newPath . $newTestFileName);

			$message = $newTestFileName . ' created in ' . str_replace(ROOT . DS, '', $newPath . $newTestFileName);
			if ($args->getOption('remove')) {
				unlink($oldPath . $testFileName);
				$message .= ', old class removed';
			}
			$io->success($message);
		}
	}

	/**
	 * @param string $name
	 * @param string|null $plugin
	 * @param string $oldPath
	 * @param string $newPath
	 * @return void
	 */
	protected function migrateTask(string $name, ?string $plugin, string $oldPath, string $newPath): void {
		$content = file_get_contents($oldPath);
		if ($content === false) {
			throw new RuntimeException('Cannot read file: ' . $oldPath);
		}

		$namespace = $plugin ? str_replace('/', '\\', $plugin) : (string)Configure::read('App.namespace');
		$newContent = str_replace('namespace ' . $namespace . '\Shell\Task;', 'namespace ' . $namespace . '\Queue\Task;', $content);

		$newContent = str_replace('use Queue\Shell\Task\QueueTask;', 'use Queue\Queue\Task;', $newContent);
		$newContent = str_replace('Task extends QueueTask', 'Task extends Task', $newContent);
		$newContent = (string)preg_replace('/class Queue(\w+)Task extends/', 'class \1Task extends', $newContent);

		$newContent = str_replace('use Queue\Shell\Task\AddInterface;', 'use Queue\Queue\AddInterface;', $newContent);

		$newContent = (string)preg_replace('/public function add\(\) {/', 'public function add(?string $data): void {', $newContent);
		$newContent = (string)preg_replace('/public function add\(\)\n/', 'public function add(?string $data): void' . PHP_EOL, $newContent);

		$methods = [
			'out',
			'hr',
			'nl',
			'verbose',
			'quiet',
			'err',
			'info',
			'success',
			'warn',
			'abort',
			'helper',
		];
		$newContent = preg_replace('/\$this-\>(' . implode('|', $methods) . ')\(/', '$this->io->\1(', $newContent);

		if (!is_dir(dirname($newPath))) {
			mkdir(dirname($newPath), 0770, true);
		}
		file_put_contents($newPath, $newContent);

		exec('php -l ' . $newPath, $output, $returnCode);
		if ($returnCode !== Command::CODE_SUCCESS) {
			$message = 'Invalid syntax for migrated class in ' . $newPath;
			if ($output) {
				$message .= PHP_EOL . implode(PHP_EOL, $output);
			}

			throw new RuntimeException($message);
		}
	}

	/**
	 * @param string $name
	 * @param string|null $plugin
	 * @param string $oldPath
	 * @param string $newPath
	 * @return void
	 */
	protected function migrateTaskTest(string $name, ?string $plugin, string $oldPath, string $newPath): void {
		$content = file_get_contents($oldPath);
		if ($content === false) {
			throw new RuntimeException('Cannot read file: ' . $oldPath);
		}

		$namespace = $plugin ? str_replace('/', '\\', $plugin) : (string)Configure::read('App.namespace');
		$newContent = str_replace('namespace ' . $namespace . '\Test\TestCase\Shell\Task;', 'namespace ' . $namespace . '\Test\TestCase\Queue\Task;', $content);

		$newContent = str_replace('use Queue\Shell\Task\QueueTask;', 'use Queue\Queue\Task;', $newContent);
		$newContent = (string)preg_replace('/\b' . preg_quote($namespace) . '\\\\Shell\\\\Task\\\\Queue(\w+)Task\b/', $namespace . '\\\\Queue\\\\Task\\\\\1Task', $newContent);

		$newContent = str_replace('Task extends QueueTask', 'Task extends Task', $newContent);
		$newContent = (string)preg_replace('/class Queue(\w+)TaskTest /', 'class \1TaskTest ', $newContent);

		$newContent = str_replace('use Queue\Shell\Task\AddInterface;', 'use Queue\Queue\AddInterface;', $newContent);

		$newContent = (string)preg_replace('/\bQueue(\w+)Task\b/', '\1Task', $newContent);

		$newContent = preg_replace('/= new ConsoleIo\((.+)\)/', '= new \Queue\Console\Io(new ConsoleIo(\1))', $newContent);

		if (!is_dir(dirname($newPath))) {
			mkdir(dirname($newPath), 0770, true);
		}
		file_put_contents($newPath, $newContent);

		exec('php -l ' . $newPath, $output, $returnCode);
		if ($returnCode !== Command::CODE_SUCCESS) {
			$message = 'Invalid syntax for migrated class in ' . $newPath;
			if ($output) {
				$message .= PHP_EOL . implode(PHP_EOL, $output);
			}
			$message .= PHP_EOL . '----------' . PHP_EOL . $newContent . PHP_EOL . '-----------';

			throw new RuntimeException($message);
		}
	}

	/**
	 * @param string|null $plugin
	 *
	 * @return string[]
	 */
	protected function getTasks(?string $plugin): array {
		$taskFinder = new OldTaskFinder();

		return $taskFinder->all($plugin);
	}

}
