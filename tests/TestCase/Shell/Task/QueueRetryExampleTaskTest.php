<?php

namespace Queue\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Queue\Shell\Task\QueueRetryExampleTask;
use Tools\TestSuite\ConsoleOutput;
use Tools\TestSuite\ToolsTestTrait;

class QueueRetryExampleTaskTest extends TestCase {

	use ToolsTestTrait;

	/**
	 * @var \Queue\Shell\Task\QueueRetryExampleTask|\PHPUnit_Framework_MockObject_MockObject
	 */
	public $Task;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	public $out;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	public $err;

	/**
	 * Setup Defaults
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$io = new ConsoleIo($this->out, $this->err);

		$this->Task = new QueueRetryExampleTask($io);
	}

	/**
	 * @return void
	 */
	public function testRun() {
		$file = TMP . 'task_retry.txt';
		file_put_contents($file, '0');

		$result = $this->Task->run([], null);

		$this->assertFalse($result);

		$this->assertTextContains('Sry, the RetryExample Job failed. Try again.', $this->out->output());
	}

	/**
	 * @return void
	 */
	public function testRunSuccess() {
		$file = TMP . 'task_retry.txt';
		file_put_contents($file, '3');

		$result = $this->Task->run([], null);

		$this->assertTrue($result);

		$this->assertTextContains('Success, the RetryExample Job was run', $this->out->output());
	}

}
