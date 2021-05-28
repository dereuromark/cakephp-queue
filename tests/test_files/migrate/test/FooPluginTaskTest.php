<?php

namespace Foo\Bar\Test\TestCase\Queue\Task;

use Foo\Bar\Queue\Task\FooPluginTask;
use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;

class FooPluginTaskTest extends TestCase {

	/**
	 * @return void
	 */
	public function testRun(): void {
		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$io = new ConsoleIo($this->out, $this->err);

		$task = new FooPluginTask($io);
	}

}
