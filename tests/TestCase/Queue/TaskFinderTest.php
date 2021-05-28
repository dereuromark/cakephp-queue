<?php

namespace Queue\Test\TestCase\Queue;

use Cake\TestSuite\TestCase;
use Queue\Queue\TaskFinder;

class TaskFinderTest extends TestCase {

	/**
	 * @var \Queue\Queue\TaskFinder
	 */
	protected $taskFinder;

	/**
	 * @return void
	 */
	public function testAllAppAndPluginTasks() {
		$this->taskFinder = new TaskFinder();

		$result = $this->taskFinder->all();

		$this->assertArrayHasKey('Queue.Example', $result);
		$this->assertArrayHasKey('Foo', $result);
		$this->assertArrayHasKey('Foo.Foo', $result);
	}

}
