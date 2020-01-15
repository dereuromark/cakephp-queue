<?php

namespace Queue\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Console\Exception\StopException;
use Cake\TestSuite\TestCase;
use Queue\Shell\Task\QueueRetryExampleTask;
use Shim\TestSuite\ConsoleOutput;
use Shim\TestSuite\TestTrait;

class QueueRetryExampleTaskTest extends TestCase {

	use TestTrait;

	/**
	 * @var \Queue\Shell\Task\QueueRetryExampleTask|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $Task;

	/**
	 * @var \Shim\TestSuite\ConsoleOutput
	 */
	protected $out;

	/**
	 * @var \Shim\TestSuite\ConsoleOutput
	 */
	protected $err;

	/**
	 * Setup Defaults
	 *
	 * @return void
	 */
	public function setUp(): void {
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

		$exception = null;
		try {
			$this->Task->run([], 0);
		} catch (\Exception $e) {
			$exception = $e;
		}

		$this->assertInstanceOf(StopException::class, $exception);
		$this->assertTextContains('Sry, the RetryExample Job failed. Try again.', $this->err->output());
	}

	/**
	 * @return void
	 */
	public function testRunSuccess() {
		$file = TMP . 'task_retry.txt';
		file_put_contents($file, '3');

		$this->Task->run([], 0);

		$this->assertTextContains('Success, the RetryExample Job was run', $this->out->output());
	}

}
