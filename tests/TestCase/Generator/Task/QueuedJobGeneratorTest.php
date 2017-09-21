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
				'Queue.Email' => '\Queue\Shell\Task\QueueEmailTask::class',
				'Queue.Example' => '\Queue\Shell\Task\QueueExampleTask::class',
				'Queue.Execute' => '\Queue\Shell\Task\QueueExecuteTask::class',
				'Queue.ProgressExample' => '\Queue\Shell\Task\QueueProgressExampleTask::class',
				'Queue.RetryExample' => '\Queue\Shell\Task\QueueRetryExampleTask::class',
				'Queue.SuperExample' => '\Queue\Shell\Task\QueueSuperExampleTask::class',
			],
		];
		$this->assertSame($expected, $result);
	}

}
