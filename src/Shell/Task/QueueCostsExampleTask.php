<?php

namespace Queue\Shell\Task;

/**
 * A Costs QueueTask example.
 */
class QueueCostsExampleTask extends QueueTask implements AddInterface {

	/**
	 * @var int
	 */
	public $costs = 55;

	/**
	 * To invoke from CLI execute:
	 * - bin/cake queue add CostsExample
	 *
	 * @return void
	 */
	public function add() {
		$this->out('CakePHP Queue CostsExample task.');
		$this->hr();
		$this->out('I will now add an example Job into the Queue.');
		$this->out('This job cannot run more than once per server (across all its workers).');
		$this->out('This job will only produce some console output on the worker that it runs on.');
		$this->out(' ');
		$this->out('To run a Worker use:');
		$this->out('    bin/cake queue runworker');
		$this->out(' ');
		$this->out('You can find the sourcecode of this task in: ');
		$this->out(__FILE__);
		$this->out(' ');

		$this->QueuedJobs->createJob('CostsExample');
		$this->success('OK, job created, now run the worker');
	}

	/**
	 * CostsExample run function.
	 * This function is executed, when a worker is executing a task.
	 *
	 * @param array $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		$this->hr();
		$this->out('CakePHP Queue CostsExample task.');

		sleep(10);

		$this->hr();
		$this->success(' -> Success, the CostsExample Job was run. <-');
	}

}
