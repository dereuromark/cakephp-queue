<?php

namespace Queue\Shell\Task;

use Queue\Model\QueueException;

/**
 * An exception throwing QueueTask example.
 */
class QueueExceptionExampleTask extends QueueTask implements AddInterface {

	/**
	 * Timeout for run, after which the Task is reassigned to a new worker.
	 *
	 * @var int
	 */
	public $timeout = 10;

	/**
	 * Example add functionality.
	 * Will create one example job in the queue, which later will be executed using run();
	 *
	 * To invoke from CLI execute:
	 * - bin/cake queue add ExceptionExample
	 *
	 * @return void
	 */
	public function add() {
		$this->out('CakePHP Queue ExceptionExample task.');
		$this->hr();
		$this->out('This is a very simple example of a QueueTask and how exceptions are handled.');
		$this->out('I will now add an example Job into the Queue.');
		$this->out('This job will only produce some console output on the worker that it runs on.');
		$this->out(' ');
		$this->out('To run a Worker use:');
		$this->out('    bin/cake queue runworker');
		$this->out(' ');
		$this->out('You can find the sourcecode of this task in: ');
		$this->out(__FILE__);
		$this->out(' ');

		$this->QueuedJobs->createJob('ExceptionExample');
		$this->success('OK, job created, now run the worker');
	}

	/**
	 * Example run function.
	 * This function is executed, when a worker is executing a task.
	 * The return parameter will determine, if the task will be marked completed, or be requeued.
	 *
	 * @param array $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 * @return void
	 * @throws \Queue\Model\QueueException
	 */
	public function run(array $data, int $jobId): void {
		$this->hr();
		$this->out('CakePHP Queue ExceptionExample task.');
		$this->hr();

		throw new QueueException('Exception demo :-)');
	}

}
