<?php

namespace Queue\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Queue\Shell\Task\QueueProgressExampleTask;
use Shim\TestSuite\ConsoleOutput;
use Shim\TestSuite\TestTrait;

class QueueProgressExampleTaskTest extends TestCase {

	use TestTrait;

	/**
	 * @var \Queue\Shell\Task\QueueProgressExampleTask|\PHPUnit\Framework\MockObject\MockObject
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

		$this->Task = new QueueProgressExampleTask($io);
	}

	/**
	 * @return void
	 */
	public function testRun() {
		$this->Task->run(['duration' => 1], 0);

		$this->assertTextContains('Success, the ProgressExample Job was run', $this->out->output());
	}

}
