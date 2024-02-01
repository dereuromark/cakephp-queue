<?php
declare(strict_types=1);

/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Queue\Queue\Task;

use Queue\Queue\AddFromBackendInterface;
use Queue\Queue\AddInterface;
use Queue\Queue\Task;

/**
 * A Simple QueueTask example.
 */
class ExampleTask extends Task implements AddInterface, AddFromBackendInterface {

	/**
	 * Timeout for run, after which the Task is reassigned to a new worker.
	 */
	public ?int $timeout = 10;

	/**
	 * Example add functionality.
	 * Will create one example job in the queue, which later will be executed using run();
	 *
	 * To invoke from CLI execute:
	 * - bin/cake queue add Queue.Example
	 *
	 * @param string|null $data Optional data for the task, make sure to "quote multi words"
	 *
	 * @return void
	 */
	public function add(?string $data): void {
		$this->io->out('CakePHP Queue Example task.');
		$this->io->hr();
		$this->io->out('This is a very simple example of a QueueTask.');
		$this->io->out('I will now add an example Job into the Queue.');
		$this->io->out('This job will only produce some console output on the worker that it runs on.');
		$this->io->out(' ');
		$this->io->out('To run a Worker use:');
		$this->io->out('    bin/cake queue run');
		$this->io->out(' ');
		$this->io->out('You can find the sourcecode of this task in: ');
		$this->io->out(__FILE__);
		$this->io->out(' ');

		$this->QueuedJobs->createJob('Queue.Example');
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
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		$this->io->hr();
		$this->io->out('CakePHP Queue Example task.');
		$this->io->hr();
		$this->io->success(' -> Success, the Example Job was run. <-');
	}

}
