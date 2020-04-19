<?php

namespace Queue\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Utility\Inflector;

class BakeQueueTaskShell extends Shell {

	/**
	 * @return void
	 */
	public function startup(): void {
		if ($this->param('quiet')) {
			$this->interactive = false;
		}

		parent::startup();
	}

	/**
	 * @param string|null $name
	 *
	 * @return bool|int|null|void
	 */
	public function generate($name = null) {
		$name = Inflector::camelize(Inflector::underscore($name));

		$name = 'Queue' . $name . 'Task';
		$plugin = $this->param('plugin') ?: null;
		if ($plugin) {
			$plugin = Inflector::camelize(Inflector::underscore($plugin));
		}

		$this->generateTask($name, $plugin);

		$this->generateTaskTest($name, $plugin);
	}

	/**
	 * @param string $name
	 * @param string $plugin
	 * @return void
	 */
	protected function generateTask($name, $plugin) {
		$path = App::classPath('Shell/Task', $plugin);
		if (!$path) {
			$this->abort('Path not found for this plugin.');
		}

		$path = array_shift($path);
		if (!is_dir($path)) {
			mkdir($path, 0770, true);
		}

		$path .= $name . '.php';
		$in = 'y';
		if (file_exists($path)) {
			$in = $this->in('Already exists, sure to overwrite?', ['y', 'n'], 'n');
		}
		if ($in !== 'y') {
			return;
		}

		$this->out('Generating: ' . ($plugin ? $plugin . '.' : '') . $name);

		$content = $this->generateTaskContent($name, $plugin);
		$this->write($path, $content);
	}

	/**
	 * @param string $name
	 * @param string $plugin
	 * @return void
	 */
	protected function generateTaskTest($name, $plugin) {
		$testsPath = $plugin ? Plugin::path($plugin) . 'tests' . DS : ROOT . DS . 'tests' . DS;

		$path = $testsPath . 'TestCase' . DS . 'Shell' . DS . 'Task' . DS;
		if (!is_dir($path)) {
			mkdir($path, 0770, true);
		}

		$path .= $name . 'Test.php';

		$in = 'y';
		if (file_exists($path)) {
			$in = $this->in('Already exists, sure to overwrite?', ['y', 'n'], 'n');
		}
		if ($in !== 'y') {
			return;
		}

		$this->out('Generating: ' . ($plugin ? $plugin . '.' : '') . $name . ' test class');

		$content = $this->generateTaskTestContent($name, $plugin);
		$this->write($path, $content);
	}

	/**
	 * Get option parser method to parse commandline options
	 *
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$subcommandParser = [
			'arguments' => [
				'name' => [
					'default' => null,
					'required' => true,
				],
			],
			'options' => [
				'plugin' => [
					'short' => 'p',
					'help' => 'Plugin',
					'default' => null,
				],
				'dry-run' => [
					'short' => 'd',
					'help' => 'Dry Run',
					'boolean' => true,
				],
			],
		];

		return parent::getOptionParser()
			->setDescription('Generate a Queue task.')
			->addSubcommand('generate', [
				'help' => 'Generate a Queue task.',
				'parser' => $subcommandParser,
			]);
	}

	/**
	 * @param string $name
	 * @param string|null $namespace PluginName
	 *
	 * @return string
	 */
	protected function generateTaskContent($name, $namespace = null) {
		if (!$namespace) {
			$namespace = 'App';
		}

		$content = <<<TXT
<?php
namespace $namespace\Shell\Task;

use Queue\Shell\Task\QueueTask;

class $name extends QueueTask {

	/**
	 * @param array \$data Payload
	 * @param int \$jobId The ID of the QueuedJob entity
	 * @return void
	 */
	public function run(array \$data, int \$jobId): void {
	}

}

TXT;

		return $content;
	}

	/**
	 * @param string $name
	 * @param string|null $namespace PluginName
	 *
	 * @return string
	 */
	protected function generateTaskTestContent($name, $namespace = null) {
		if (!$namespace) {
			$namespace = 'App';
		}

		$testName = $name . 'Test';
		$taskClassNamespace = $namespace . '\Shell\\Task\\' . $name;

		$content = <<<TXT
<?php
namespace $namespace\Test\TestCase\Shell\Task;

use Cake\TestSuite\TestCase;
use $taskClassNamespace;

class $testName extends TestCase {

	/**
	 * @var string[]
	 */
	protected \$fixtures = [
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
	 * @param string $path
	 * @param string $content
	 * @return void
	 */
	protected function write($path, $content) {
		if ($this->param('dry-run')) {
			return;
		}

		file_put_contents($path, $content);
	}

}
