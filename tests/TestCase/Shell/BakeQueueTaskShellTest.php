<?php

namespace Queue\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Queue\Shell\BakeQueueTaskShell;
use Shim\TestSuite\ConsoleOutput;
use Shim\TestSuite\TestTrait;

class BakeQueueTaskShellTest extends TestCase {

	use TestTrait;

	/**
	 * @var \Queue\Shell\BakeQueueTaskShell|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $shell;

	/**
	 * @var \Shim\TestSuite\ConsoleOutput
	 */
	protected $out;

	/**
	 * @var \Shim\TestSuite\ConsoleOutput
	 */
	protected $err;

	/**
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Queue.QueuedJobs',
		'plugin.Queue.QueueProcesses',
	];

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

		$this->shell = new BakeQueueTaskShell($io);
		$this->shell->initialize();
	}

	/**
	 * @return void
	 */
	public function testGenerate() {
		$this->shell->runCommand(['generate', 'FooBar', '-d']);

		$output = $this->out->output();
		$this->assertStringContainsString('Generating: QueueFooBarTask', $output);
		$this->assertStringContainsString('Generating: QueueFooBarTask test class', $output);
	}

}
