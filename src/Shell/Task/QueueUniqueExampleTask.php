<?php

namespace Queue\Shell\Task;

/**
 * A Unique QueueTask example.
 */
class QueueUniqueExampleTask extends QueueTask implements AddInterface {

	/**
	 * @var bool
	 */
	public $unique = true;

	/**
	 * To invoke from CLI execute:
	 * - bin/cake queue add UniqueExample
	 *
	 * @return void
	 */
	public function add() {
		$this->out('CakePHP Queue UniqueExample task.');
		$this->hr();
		$this->out('I will now add an example Job into the Queue.');
		$this->out('This job cannot run more than once across all workers.');
		$this->out('This job will only produce some console output on the worker that it runs on.');
		$this->out(' ');
		$this->out('To run a Worker use:');
		$this->out('    bin/cake queue runworker');
		$this->out(' ');
		$this->out('You can find the sourcecode of this task in: ');
		$this->out(__FILE__);
		$this->out(' ');

		$this->QueuedJobs->createJob('UniqueExample');
		$this->success('OK, job created, now run the worker');
	}

	/**
	 * UniqueExample run function.
	 * This function is executed, when a worker is executing a task.
	 * The return parameter will determine, if the task will be marked completed, or be requeued.
	 *
	 * @param array $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		$this->hr();
		$this->out('CakePHP Queue UniqueExample task.');

		sleep(10);

		$this->hr();
		$this->success(' -> Success, the UniqueExample Job was run. <-');
	}

}
