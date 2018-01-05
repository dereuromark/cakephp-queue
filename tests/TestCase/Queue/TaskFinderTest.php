<?php

namespace Queue\Test\TestCase\Shell;

use Cake\TestSuite\TestCase;
use Queue\Queue\TaskFinder;

class TaskFinderTest extends TestCase {

	/**
	 * @var \Queue\Shell\QueueShell|\PHPUnit_Framework_MockObject_MockObject
	 */
	public $QueueShell;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	public $out;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	public $err;

	/**
	 * Fixtures to load
	 *
	 * @var array
	 */
	public $fixtures = [
		'plugin.Queue.QueuedJobs',
		'plugin.Queue.QueueProcesses',
	];

	/**
	 * Setup Defaults
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
	}

	/**
	 * @return void
	 */
	public function testAllAppAndPluginTasks() {
		$this->taskFinder = new TaskFinder();

		$result = $this->taskFinder->allAppAndPluginTasks();
		$this->assertCount(7, $result);
		$this->assertArraySubset(['QueueFoo'], $result);
		$this->assertTrue(!in_array('Foo.QueueFoo', $result));
	}

}
