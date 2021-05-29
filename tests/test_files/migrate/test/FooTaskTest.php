<?php

namespace TestApp\Test\TestCase\Queue\Task;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use TestApp\Queue\Task\FooTask;

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

	/**
	 * @return \PHPUnit\Framework\MockObject\MockObject|\TestApp\Queue\Task\FooTask
	 */
	protected function getQueueFooTask(): FooTask {
		/** @var \TestApp\Queue\Task\FooTask|\PHPUnit\Framework\MockObject\MockObject $mock */
		$mock = $this->getMockBuilder(FooTask::class)->setMethods(['getArray'])->getMock();
		$mock->expects($this->any())->method('getArray')->willReturn([]);

		return $mock;
	}

}
