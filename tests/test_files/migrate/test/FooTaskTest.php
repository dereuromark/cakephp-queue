<?php

namespace TestApp\Test\TestCase\Queue\Task;

use TestApp\Queue\Task\FooTask;
use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;

class FooTaskTest extends TestCase {

	/**
	 * @return void
	 */
	public function testRun(): void {
		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$io = new ConsoleIo($this->out, $this->err);

		$task = new FooTask($io);
	}

}
