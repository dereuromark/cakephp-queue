<?php

namespace Queue\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Queue\Shell\BakeQueueTaskShell;
use Tools\TestSuite\ConsoleOutput;
use Tools\TestSuite\ToolsTestTrait;

class BakeQueueTaskShellTest extends TestCase {

	use ToolsTestTrait;

	/**
	 * @var \Queue\Shell\BakeQueueTaskShell|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $shell;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	protected $out;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	protected $err;

	/**
	 * Fixtures to load
	 *
	 * @var array
	 */
	public $fixtures = [
		'plugin.Queue.QueuedJobs',
		'plugin.Queue.QueueProcesses',
	];

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

		$this->shell = new BakeQueueTaskShell($io);
		$this->shell->initialize();
	}

	/**
	 * @return void
	 */
	public function testGenerate() {
		$this->shell->runCommand(['generate', 'FooBar', '-d']);

		$output = $this->out->output();
		$this->assertContains('Generating: QueueFooBarTask', $output);
		$this->assertContains('Generating: QueueFooBarTask test class', $output);
	}

}
