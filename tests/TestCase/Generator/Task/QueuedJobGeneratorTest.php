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
	public function setUp(): void {
		parent::setUp();
		$this->task = new QueuedJobTask();
	}

	/**
	 * @return void
	 */
	public function testCollect() {
		$result = $this->task->collect();

		$this->assertCount(2, $result);

		/** @var \IdeHelper\Generator\Directive\ExpectedArguments $directive */
		$directive = array_shift($result);
		$this->assertSame('\Queue\Model\Table\QueuedJobsTable::createJob()', $directive->toArray()['method']);

		$list = $directive->toArray()['list'];
		$expected = [
			'Execute' => "'Execute'",
			'ProgressExample' => "'ProgressExample'",
		];
		foreach ($expected as $name => $value) {
			$this->assertSame($value, $list[$name]);
		}

		/** @var \IdeHelper\Generator\Directive\ExpectedArguments $directive */
		$directive = array_shift($result);
		$this->assertSame('\Queue\Model\Table\QueuedJobsTable::isQueued()', $directive->toArray()['method']);

		$list = $directive->toArray()['list'];
		$this->assertNotEmpty($list);
	}

}
