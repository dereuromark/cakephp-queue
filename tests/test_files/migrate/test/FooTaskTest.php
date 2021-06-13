<?php

namespace TestApp\Test\TestCase\Queue\Task;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Shim\TestSuite\ConsoleOutput;
use TestApp\Queue\Task\FooTask;

class FooTaskTest extends TestCase {

	/**
	 * @return void
	 */
	public function testRun(): void {
		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$io = new \Queue\Console\Io(new ConsoleIo($this->out, $this->err));

		$task = new FooTask($io);
	}

}
