<?php

namespace TestApp\Test\TestCase\Shell\Task;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Shim\TestSuite\ConsoleOutput;
use TestApp\Shell\Task\QueueFooTask;

class QueueFooTaskTest extends TestCase {

	/**
	 * @return void
	 */
	public function testRun(): void {
		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$io = new ConsoleIo($this->out, $this->err);

		$task = new QueueFooTask($io);
	}

}
