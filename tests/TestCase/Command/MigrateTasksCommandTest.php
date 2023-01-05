<?php
declare(strict_types = 1);

namespace Queue\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Queue\Command\MigrateTasksCommand;
use Shim\TestSuite\TestTrait;

/**
 * @uses \Queue\Command\MigrateTasksCommand
 */
class MigrateTasksCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;
	use TestTrait;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		//$this->useCommandRunner();
	}

	/**
	 * @return void
	 */
	public function testMigrateTask(): void {
		$path = sys_get_temp_dir() . DS . 'FooTask.php';
		$params = [
			'Foo',
			null,
			PLUGIN_ROOT . DS . 'tests' . DS . 'test_files' . DS . 'migrate' . DS . 'QueueFooTask.php',
			$path,
		];
		$command = new MigrateTasksCommand();
		$this->invokeMethod($command, 'migrateTask', $params);

		$expected = PLUGIN_ROOT . DS . 'tests' . DS . 'test_files' . DS . 'migrate' . DS . 'FooTask.php';
		$this->assertFileEquals($expected, $path);
	}

	/**
	 * @return void
	 */
	public function testMigrateTaskPlugin(): void {
		$path = sys_get_temp_dir() . DS . 'FooPluginTask.php';
		$params = [
			'Foo',
			'Foo/Bar',
			PLUGIN_ROOT . DS . 'tests' . DS . 'test_files' . DS . 'migrate' . DS . 'QueueFooPluginTask.php',
			$path,
		];
		$command = new MigrateTasksCommand();
		$this->invokeMethod($command, 'migrateTask', $params);

		$expected = PLUGIN_ROOT . DS . 'tests' . DS . 'test_files' . DS . 'migrate' . DS . 'FooPluginTask.php';
		$this->assertFileEquals($expected, $path);
	}

	/**
	 * @return void
	 */
	public function testMigrateTaskTest(): void {
		$path = sys_get_temp_dir() . DS . 'FooTaskTest.php';
		$params = [
			'Foo',
			null,
			PLUGIN_ROOT . DS . 'tests' . DS . 'test_files' . DS . 'migrate' . DS . 'test' . DS . 'QueueFooTaskTest.php',
			$path,
		];
		$command = new MigrateTasksCommand();
		$this->invokeMethod($command, 'migrateTaskTest', $params);

		$expected = PLUGIN_ROOT . DS . 'tests' . DS . 'test_files' . DS . 'migrate' . DS . 'test' . DS . 'FooTaskTest.php';
		$this->assertFileEquals($expected, $path);
	}

	/**
	 * @return void
	 */
	public function testMigrateTaskTestPlugin(): void {
		$path = sys_get_temp_dir() . DS . 'FooPluginTaskTest.php';
		$params = [
			'Foo',
			'Foo/Bar',
			PLUGIN_ROOT . DS . 'tests' . DS . 'test_files' . DS . 'migrate' . DS . 'test' . DS . 'QueueFooPluginTaskTest.php',
			$path,
		];
		$command = new MigrateTasksCommand();
		$this->invokeMethod($command, 'migrateTaskTest', $params);

		$expected = PLUGIN_ROOT . DS . 'tests' . DS . 'test_files' . DS . 'migrate' . DS . 'test' . DS . 'FooPluginTaskTest.php';
		$this->assertFileEquals($expected, $path);
	}

	/**
	 * Test execute method
	 *
	 * @return void
	 */
	public function testExecute(): void {
		$this->exec('queue migrate_tasks');

		$output = $this->_out->output();
		$this->assertStringContainsString('1 shell tasks to migrate...', $output);
		$this->assertExitCode(0);
	}

}
