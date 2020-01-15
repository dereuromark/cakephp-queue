<?php

namespace Queue\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Queue\Shell\Task\QueueMonitorExampleTask;
use Shim\TestSuite\ConsoleOutput;
use Shim\TestSuite\TestTrait;

class QueueMonitorExampleTaskTest extends TestCase {

	use TestTrait;

	/**
	 * @var \Queue\Shell\Task\QueueMonitorExampleTask|\PHPUnit\Framework\MockObject\MockObject
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

		$this->Task = new QueueMonitorExampleTask($io);
	}

	/**
	 * @return void
	 */
	public function testRun() {
		$this->Task->run([], 0);

		$this->assertTextContains('Success, the MonitorExample Job was run', $this->out->output());
	}

}
