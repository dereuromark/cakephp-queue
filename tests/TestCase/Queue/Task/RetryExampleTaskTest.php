<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Queue\Task;

use Cake\Console\ConsoleIo;
use Cake\Console\Exception\StopException;
use Cake\TestSuite\TestCase;
use Exception;
use Queue\Console\Io;
use Queue\Queue\Task\RetryExampleTask;
use Shim\TestSuite\ConsoleOutput;
use Shim\TestSuite\TestTrait;

class RetryExampleTaskTest extends TestCase {

	use TestTrait;

	/**
	 * @var \Queue\Queue\Task\RetryExampleTask|\PHPUnit\Framework\MockObject\MockObject
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
		$io = new Io(new ConsoleIo($this->out, $this->err));

		$this->Task = new RetryExampleTask($io);
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
		} catch (Exception $e) {
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
