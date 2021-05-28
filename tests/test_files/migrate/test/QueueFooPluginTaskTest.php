<?php

namespace Foo\Bar\Test\TestCase\Shell\Task;

use Foo\Bar\Shell\Task\QueueFooPluginTask;
use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;

class QueueFooPluginTaskTest extends TestCase {

	/**
	 * @return void
	 */
	public function testRun(): void {
		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$io = new ConsoleIo($this->out, $this->err);

		$task = new QueueFooPluginTask($io);
	}

}
