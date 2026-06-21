<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Shim\TestSuite\TestTrait;

/**
 * @uses \Queue\Command\BakeQueueTaskCommand
 */
class BakeQueueTaskCommandTest extends TestCase {

	use TestTrait;
	use ConsoleIntegrationTestTrait;

	/**
	 * @var string
	 */
	protected $filePath = ROOT . DS . 'src' . DS . 'Queue' . DS . 'Task' . DS;

	/**
	 * @var string
	 */
	protected $testFilePath = ROOT . DS . 'tests' . DS . 'TestCase' . DS . 'Queue' . DS . 'Task' . DS;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->loadPlugins(['Queue']);

		$this->removeFiles();
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		$this->removeFiles();
	}

	/**
	 * Test execute method
	 *
	 * @return void
	 */
	public function testExecute(): void {
		$this->exec('bake queue_task FooBarBaz -a -f');

		$output = $this->_out->output();
		$this->assertStringContainsString('Creating file', $output);
		$this->assertStringContainsString('<success>Wrote</success>', $output);

		$file = $this->filePath . 'FooBarBazTask.php';
		$expected = TESTS . 'test_files' . DS . 'bake' . DS . 'task.php';
		$this->assertFileEquals($expected, $file);

		$file = $this->testFilePath . 'FooBarBazTaskTest.php';
		$expected = TESTS . 'test_files' . DS . 'bake' . DS . 'task_test.php';
		$this->assertFileEquals($expected, $file);
	}

	/**
	 * @return void
	 */
	public function testExecuteWithSubFolder(): void {
		$this->exec('bake queue_task Sub/FooBarBaz -a -f');

		$output = $this->_out->output();
		$this->assertStringContainsString('Creating file', $output);
		$this->assertStringContainsString('<success>Wrote</success>', $output);

		$file = $this->filePath . 'Sub' . DS . 'FooBarBazTask.php';
		$expected = TESTS . 'test_files' . DS . 'bake' . DS . 'Sub' . DS . 'task.php';
		$this->assertFileEquals($expected, $file);

		$file = $this->testFilePath . 'Sub' . DS . 'FooBarBazTaskTest.php';
		$expected = TESTS . 'test_files' . DS . 'bake' . DS . 'Sub' . DS . 'task_test.php';
		$this->assertFileEquals($expected, $file);
	}

	/**
	 * @return void
	 */
	protected function removeFiles(): void {
		if ($this->isDebug()) {
			return;
		}

		$file = $this->filePath . 'FooBarBazTask.php';
		if (file_exists($file)) {
			unlink($file);
		}
		$file = $this->filePath . 'Sub' . DS . 'FooBarBazTask.php';
		if (file_exists($file)) {
			unlink($file);
		}

		$testFile = $this->testFilePath . 'FooBarBazTaskTest.php';
		if (file_exists($testFile)) {
			unlink($testFile);
		}
		$testFile = $this->testFilePath . 'Sub' . DS . 'FooBarBazTaskTest.php';
		if (file_exists($testFile)) {
			unlink($testFile);
		}
	}

}
