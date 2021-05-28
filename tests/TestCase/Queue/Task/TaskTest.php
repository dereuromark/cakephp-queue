<?php

namespace Queue\Test\TestCase\Queue;

use Cake\TestSuite\TestCase;
use Queue\Queue\Task\ExampleTask;
use TestApp\Queue\Task\FooTaskTest;

class TaskTest extends TestCase {

	/**
	 * @return void
	 */
	public function testTaskName() {
		$name = FooTaskTest::taskName();
		$this->assertSame('Foo', $name);

		$name = ExampleTask::taskName();
		$this->assertSame('Queue.Example', $name);
	}

}
