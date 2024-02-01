<?php
declare(strict_types=1);

namespace Queue\Queue\Task;

use Queue\Model\QueueException;
use Queue\Queue\AddFromBackendInterface;
use Queue\Queue\AddInterface;
use Queue\Queue\Task;

/**
 * An exception throwing QueueTask example.
 */
class ExceptionExampleTask extends Task implements AddInterface, AddFromBackendInterface {

	/**
	 * Timeout for run, after which the Task is reassigned to a new worker.
	 */
	public ?int $timeout = 10;

	/**
	 * Example add functionality.
	 * Will create one example job in the queue, which later will be executed using run();
	 *
	 * To invoke from CLI execute:
	 * - bin/cake queue add Queue.ExceptionExample
	 *
	 * @param string|null $data
	 *
	 * @return void
	 */
	public function add(?string $data): void {
		$this->io->out('CakePHP Queue ExceptionExample task.');
		$this->io->hr();
		$this->io->out('This is a very simple example of a QueueTask and how exceptions are handled.');
		$this->io->out('I will now add an example Job into the Queue.');
		$this->io->out('This job will only produce some console output on the worker that it runs on.');
		$this->io->out(' ');
		$this->io->out('To run a Worker use:');
		$this->io->out('    bin/cake queue run');
		$this->io->out(' ');
		$this->io->out('You can find the sourcecode of this task in: ');
		$this->io->out(__FILE__);
		$this->io->out(' ');

		$this->QueuedJobs->createJob('Queue.ExceptionExample');
		$this->io->success('OK, job created, now run the worker');
	}

	/**
	 * Example run function.
	 * This function is executed, when a worker is executing a task.
	 * The return parameter will determine, if the task will be marked completed, or be requeued.
	 *
	 * @param array<string, mixed> $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 *
	 * @throws \Queue\Model\QueueException
	 *
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		$this->io->hr();
		$this->io->out('CakePHP Queue ExceptionExample task.');
		$this->io->hr();

		throw new QueueException('Exception demo :-)');
	}

}
