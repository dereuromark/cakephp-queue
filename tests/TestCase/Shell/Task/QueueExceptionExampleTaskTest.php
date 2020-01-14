<?php

namespace Queue\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Queue\Shell\Task\QueueExceptionExampleTask;
use RuntimeException;
use Tools\TestSuite\ConsoleOutput;
use Tools\TestSuite\ToolsTestTrait;

class QueueExceptionExampleTaskTest extends TestCase {

	use ToolsTestTrait;

	/**
	 * @var array
	 */
	public $fixtures = [
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * @var \Queue\Shell\Task\QueueExceptionExampleTask|\PHPUnit\Framework\MockObject\MockObject
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
	public function setUp(): void {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$io = new ConsoleIo($this->out, $this->err);

		$this->Task = new QueueExceptionExampleTask($io);
	}

	/**
	 * @return void
	 */
	public function testRun() {
		$this->expectException(RuntimeException::class);

		$this->Task->run([], null);
	}

}
