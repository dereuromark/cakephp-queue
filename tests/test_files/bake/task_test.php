<?php

namespace TestApp\Test\TestCase\Queue\Task;

use Cake\TestSuite\TestCase;
use TestApp\Queue\Task\FooBarBazTask;

class FooBarBazTaskTest extends TestCase {

	/**
	 * @var list<string>
	 */
	protected array $fixtures = [
		'plugin.Queue.QueuedJobs',
		'plugin.Queue.QueueProcesses',
	];

	/**
	 * @return void
	 */
	public function testRun(): void {
		$task = new FooBarBazTask();

		//TODO
		//$task->run($data, $jobId);
	}

}
