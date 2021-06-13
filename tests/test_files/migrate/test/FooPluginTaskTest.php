<?php

namespace Foo\Bar\Test\TestCase\Queue\Task;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Foo\Bar\Queue\Task\FooPluginTask;
use Shim\TestSuite\ConsoleOutput;

class FooPluginTaskTest extends TestCase {

	/**
	 * @return void
	 */
	public function testRun(): void {
		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$io = new \Queue\Console\Io(new ConsoleIo($this->out, $this->err));

		$task = new FooPluginTask($io);
	}

}
