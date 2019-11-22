<?php

namespace Queue\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Queue\Shell\Task\QueueMonitorExampleTask;
use Tools\TestSuite\ConsoleOutput;
use Tools\TestSuite\ToolsTestTrait;

class QueueMonitorExampleTaskTest extends TestCase {

	use ToolsTestTrait;

	/**
	 * @var \Queue\Shell\Task\QueueMonitorExampleTask|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $Task;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	protected $out;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	protected $err;

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

		$this->Task = new QueueMonitorExampleTask($io);
	}

	/**
	 * @return void
	 */
	public function testRun() {
		$this->Task->run([], null);

		$this->assertTextContains('Success, the MonitorExample Job was run', $this->out->output());
	}

}
