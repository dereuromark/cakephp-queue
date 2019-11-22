<?php

namespace Queue\Test\TestCase\Shell;

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

		$result = $this->taskFinder->allAppAndPluginTasks();
		$this->assertCount(11, $result);

		$this->assertTrue(in_array('QueueFoo', $result, true));
		$this->assertTrue(!in_array('Foo.QueueFoo', $result, true));
	}

}
