<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Queue;

use Cake\TestSuite\TestCase;
use Queue\Queue\Task\ExampleTask;
use TestApp\Queue\Task\FooTask;

class TaskTest extends TestCase {

	/**
	 * @return void
	 */
	public function testTaskName() {
		$name = FooTask::taskName();
		$this->assertSame('Foo', $name);

		$name = ExampleTask::taskName();
		$this->assertSame('Queue.Example', $name);
	}

}
