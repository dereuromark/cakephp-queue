<?php
declare(strict_types=1);

namespace Queue\Queue\Task;

use Queue\Queue\AddFromBackendInterface;
use Queue\Queue\AddInterface;
use Queue\Queue\Task;

/**
 * A Costs QueueTask example.
 */
class CostsExampleTask extends Task implements AddInterface, AddFromBackendInterface {

	/**
	 * @var int
	 */
	public int $costs = 55;

	/**
	 * @var int
	 */
	protected int $sleep = 10;

	/**
	 * To invoke from CLI execute:
	 * - bin/cake queue add Queue.CostsExample
	 *
	 * @param string|null $data
	 *
	 * @return void
	 */
	public function add(?string $data): void {
		$this->io->out('CakePHP Queue CostsExample task.');
		$this->io->hr();
		$this->io->out('I will now add an example Job into the Queue.');
		$this->io->out('This job cannot run more than once per server (across all its workers).');
		$this->io->out('This job will only produce some console output on the worker that it runs on.');
		$this->io->out(' ');
		$this->io->out('To run a Worker use:');
		$this->io->out('    bin/cake queue run');
		$this->io->out(' ');
		$this->io->out('You can find the sourcecode of this task in: ');
		$this->io->out(__FILE__);
		$this->io->out(' ');

		$this->QueuedJobs->createJob('Queue.CostsExample');
		$this->io->success('OK, job created, now run the worker');
	}

	/**
	 * CostsExample run function.
	 * This function is executed, when a worker is executing a task.
	 *
	 * @param array<string, mixed> $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 *
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		$this->io->hr();
		$this->io->out('CakePHP Queue CostsExample task.');

		sleep($this->sleep);

		$this->io->hr();
		$this->io->success(' -> Success, the CostsExample Job was run. <-');
	}

	/**
	 * @param int $seconds
	 *
	 * @return void
	 */
	public function setSleep(int $seconds): void {
		$this->sleep = $seconds;
	}

}
