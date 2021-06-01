<?php

namespace Queue\Test\TestCase\Queue\Task;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Queue\Console\Io;
use Queue\Queue\Task\ExceptionExampleTask;
use RuntimeException;
use Shim\TestSuite\ConsoleOutput;
use Shim\TestSuite\TestTrait;

class ExceptionExampleTaskTest extends TestCase {

	use TestTrait;

	/**
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * @var \Queue\Queue\Task\ExceptionExampleTask|\PHPUnit\Framework\MockObject\MockObject
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

		$this->Task = new ExceptionExampleTask($io);
	}

	/**
	 * @return void
	 */
	public function testRun() {
		$this->expectException(RuntimeException::class);

		$this->Task->run([], 0);
	}

}
