<?php

namespace Queue\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Queue\Shell\Task\QueueProgressExampleTask;
use Tools\TestSuite\ConsoleOutput;
use Tools\TestSuite\ToolsTestTrait;

class QueueProgressExampleTaskTest extends TestCase {

	use ToolsTestTrait;

	/**
	 * @var \Queue\Shell\Task\QueueProgressExampleTask|\PHPUnit\Framework\MockObject\MockObject
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

		$this->Task = new QueueProgressExampleTask($io);
	}

	/**
	 * @return void
	 */
	public function testRun() {
		$this->Task->run(['duration' => 1], null);

		$this->assertTextContains('Success, the ProgressExample Job was run', $this->out->output());
	}

}
