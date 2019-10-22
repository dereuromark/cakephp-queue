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

		$this->assertCount(1, $result);

		/** @var \IdeHelper\Generator\Directive\Override $directive */
		$directive = array_shift($result);
		$this->assertSame('\Queue\Model\Table\QueuedJobsTable::createJob(0)', $directive->toArray()['method']);

		$map = $directive->toArray()['map'];
		$expected = [
			'CostsExample' => '\Queue\Shell\Task\QueueCostsExampleTask::class',
			'Email' => '\Queue\Shell\Task\QueueEmailTask::class',
			'Example' => '\Queue\Shell\Task\QueueExampleTask::class',
			'ExceptionExample' => '\Queue\Shell\Task\QueueExceptionExampleTask::class',
			'Execute' => '\Queue\Shell\Task\QueueExecuteTask::class',
			'Foo' => '\App\Shell\Task\QueueFooTask::class',
			'MonitorExample' => '\Queue\Shell\Task\QueueMonitorExampleTask::class',
			'ProgressExample' => '\Queue\Shell\Task\QueueProgressExampleTask::class',
			'RetryExample' => '\Queue\Shell\Task\QueueRetryExampleTask::class',
			'SuperExample' => '\Queue\Shell\Task\QueueSuperExampleTask::class',
			'UniqueExample' => '\Queue\Shell\Task\QueueUniqueExampleTask::class',
		];
		$this->assertSame($expected, $map);
	}

}
