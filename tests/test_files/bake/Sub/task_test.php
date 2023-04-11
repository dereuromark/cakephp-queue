<?php

namespace TestApp\Test\TestCase\Queue\Task\Sub;

use Cake\TestSuite\TestCase;
use TestApp\Queue\Task\Sub\FooBarBazTask;

class FooBarBazTaskTest extends TestCase {

	/**
	 * @var array<string>
	 */
	protected $fixtures = [
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
