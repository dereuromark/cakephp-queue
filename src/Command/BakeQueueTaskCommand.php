<?php
declare(strict_types=1);

namespace Queue\Command;

use Bake\Command\SimpleBakeCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Core\Plugin;

/**
 * Command class for generating queue task files and their tests.
 */
class BakeQueueTaskCommand extends SimpleBakeCommand {

	/**
	 * Task name used in path generation.
	 *
	 * @var string
	 */
	public string $pathFragment = 'Queue/Task/';

	/**
	 * @var string
	 */
	protected string $_name;

	/**
	 * @inheritDoc
	 */
	public static function defaultName(): string {
		return 'bake queue_task';
	}

	/**
	 * @inheritDoc
	 */
	public function bake(string $name, Arguments $args, ConsoleIo $io): void {
		$this->_name = $name . 'Task';

		parent::bake($name, $args, $io);
	}

	/**
	 * Generate a test case.
	 *
	 * @param string $name The class to bake a test for.
	 * @param \Cake\Console\Arguments $args The console arguments
	 * @param \Cake\Console\ConsoleIo $io The console io
	 *
	 * @return void
	 */
	public function bakeTest(string $name, Arguments $args, ConsoleIo $io): void {
		if ($args->getOption('no-test')) {
			return;
		}

		$className = $name . 'Task';
		$io->out('Generating: ' . $className . ' test class');

		$plugin = (string)$args->getOption('plugin');
		$namespace = $plugin ? str_replace('/', DS, $plugin) : Configure::read('App.namespace');

		$content = $this->generateTaskTestContent($className, $namespace);
		$path = $plugin ? Plugin::path($plugin) : ROOT . DS;
		$path .= 'tests/TestCase/Queue/Task/' . $className . 'Test.php';

		$io->createFile($path, $content, (bool)$args->getOption('force'));
	}

	/**
	 * @param string $name
	 * @param string $namespace
	 *
	 * @return string
	 */
	protected function generateTaskTestContent(string $name, string $namespace): string {
		$testName = $name . 'Test';
		$subNamespace = '';
		$pos = strrpos($testName, '/');
		if ($pos !== false) {
			$subNamespace = '\\' . substr($testName, 0, $pos);
			$testName = substr($testName, $pos + 1);
		}
		$taskClassNamespace = $namespace . '\Queue\\Task\\' . str_replace(DS, '\\', $name);

		if (strpos($name, '/') !== false) {
			$parts = explode('/', $name);
			$name = array_pop($parts);
		}

		$content = <<<TXT
<?php

namespace $namespace\Test\TestCase\Queue\Task$subNamespace;

use Cake\TestSuite\TestCase;
use $taskClassNamespace;

class $testName extends TestCase {

	/**
	 * @var list<string>
	 */
	protected array \$fixtures = [
		'plugin.Queue.QueuedJobs',
		'plugin.Queue.QueueProcesses',
	];

	/**
	 * @return void
	 */
	public function testRun(): void {
		\$task = new $name();

		//TODO
		//\$task->run(\$data, \$jobId);
	}

}

TXT;

		return $content;
	}

	/**
	 * @inheritDoc
	 */
	public function template(): string {
		return 'Queue.Task/task';
	}

	/**
	 * @inheritDoc
	 */
	public function templateData(Arguments $arguments): array {
		$name = $this->_name;
		$namespace = Configure::read('App.namespace');
		$pluginPath = '';
		if ($this->plugin) {
			$namespace = $this->_pluginNamespace($this->plugin);
			$pluginPath = $this->plugin . '.';
		}

		$namespace .= '\\Queue\\Task';

		$namespacePart = null;
		if (strpos($name, '/') !== false) {
			$parts = explode('/', $name);
			$name = array_pop($parts);
			$namespacePart = implode('\\', $parts);
		}
		if ($namespacePart) {
			$namespace .= '\\' . $namespacePart;
		}

		return [
			'plugin' => $this->plugin,
			'pluginPath' => $pluginPath,
			'namespace' => $namespace,
			'subNamespace' => $namespacePart ? ($namespacePart . '/') : '',
			'name' => $name,
			'add' => $arguments->getOption('add'),
		];
	}

	/**
	 * @inheritDoc
	 */
	public function name(): string {
		return 'queue_task';
	}

	/**
	 * @inheritDoc
	 */
	public function fileName(string $name): string {
		return $name . 'Task.php';
	}

	/**
	 * Gets the option parser instance and configures it.
	 *
	 * @param \Cake\Console\ConsoleOptionParser $parser Parser instance
	 *
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser = parent::buildOptionParser($parser);
		$parser->addOption('add', [
			'help' => 'Task implements AddInterface',
			'boolean' => true,
			'short' => 'a',
		]);

		return $parser;
	}

}
