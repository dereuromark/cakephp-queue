<?php

namespace Queue\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Queue\Shell\Task\QueueSuperExampleTask;
use Tools\TestSuite\ConsoleOutput;
use Tools\TestSuite\ToolsTestTrait;

class QueueSuperExampleTaskTest extends TestCase {

	use ToolsTestTrait;

	/**
	 * @var array
	 */
	public $fixtures = [
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * @var \Queue\Shell\Task\QueueSuperExampleTask|\PHPUnit\Framework\MockObject\MockObject
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

		$this->Task = new QueueSuperExampleTask($io);
	}

	/**
	 * @return void
	 */
	public function testRun() {
		$this->Task->run([], null);

		$this->assertTextContains('Success, the SuperExample Job was run', $this->out->output());
	}

}
