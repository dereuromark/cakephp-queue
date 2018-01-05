<?php

namespace Queue\Test\TestCase\Generator\Task;

use Cake\TestSuite\TestCase;
use Queue\Generator\Task\QueuedJobTask;

class QueuedJobGeneratorTest extends TestCase {

	/**
	 * @var \Queue\Generator\Task\QueuedJobTask
	 */
	protected $task;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->task = new QueuedJobTask();
	}

	/**
	 * @return void
	 */
	public function testCollect() {
		$result = $this->task->collect();

		$expected = [
			'\Queue\Model\Table\QueuedJobsTable::createJob(0)' => [
				'Email' => '\Queue\Shell\Task\QueueEmailTask::class',
				'Example' => '\Queue\Shell\Task\QueueExampleTask::class',
				'Execute' => '\Queue\Shell\Task\QueueExecuteTask::class',
				'ProgressExample' => '\Queue\Shell\Task\QueueProgressExampleTask::class',
				'RetryExample' => '\Queue\Shell\Task\QueueRetryExampleTask::class',
				'SuperExample' => '\Queue\Shell\Task\QueueSuperExampleTask::class',
				'Foo' => '\App\Shell\Task\QueueFooTask::class',
			],
		];
		$this->assertSame($expected, $result);
	}

}
