<?php
declare(strict_types=1);

namespace Queue\Queue\Task;

use Queue\Queue\AddFromBackendInterface;
use Queue\Queue\AddInterface;
use Queue\Queue\Task;

/**
 * A Simple QueueTask example that runs for a while and updates the progress field.
 */
class ProgressExampleTask extends Task implements AddInterface, AddFromBackendInterface {

	/**
	 * Timeout for run, after which the Task is reassigned to a new worker.
	 */
	public ?int $timeout = 120;

	/**
	 * @var int
	 */
	public const MINUTE = 60;

	/**
	 * Example add functionality.
	 * Will create one example job in the queue, which later will be executed using run();
	 *
	 * To invoke from CLI execute:
	 * - bin/cake queue add Queue.ProgressExample
	 *
	 * @param string|null $data
	 *
	 * @return void
	 */
	public function add(?string $data): void {
		$this->io->out('CakePHP Queue ProgressExample task.');
		$this->io->hr();
		$this->io->out('This is a very simple but long running example of a QueueTask.');
		$this->io->out('I will now add the Job into the Queue.');
		$this->io->out('This job will need at least 2 minutes to complete.');
		$this->io->out(' ');
		$this->io->out('To run a Worker use:');
		$this->io->out('    bin/cake queue run');
		$this->io->out(' ');
		$this->io->out('You can find the sourcecode of this task in:');
		$this->io->out(__FILE__);
		$this->io->out(' ');

		$data = [
			'duration' => 2 * static::MINUTE,
		];
		$this->QueuedJobs->createJob('Queue.ProgressExample', $data);
		$this->io->success('OK, job created, now run the worker');
	}

	/**
	 * Example run function.
	 * This function is executed, when a worker is executing a task.
	 * The return parameter will determine, if the task will be marked completed, or be requeued.
	 *
	 * Defaults to 120 seconds
	 *
	 * @param array<string, mixed> $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 *
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		$this->io->hr();
		$this->io->out('CakePHP Queue ProgressExample task.');
		$seconds = !empty($data['duration']) ? (int)$data['duration'] : 2 * static::MINUTE;

		$this->io->out('A total of ' . $seconds . ' seconds need to pass...');
		for ($i = 0; $i < $seconds; $i++) {
			sleep(1);
			$this->QueuedJobs->updateProgress($jobId, ($i + 1) / $seconds, 'Status Test ' . ($i + 1) . 's');
		}
		$this->QueuedJobs->updateProgress($jobId, 1, 'Status Test Done');

		$this->io->hr();
		$this->io->success(' -> Success, the ProgressExample Job was run. <-');
	}

}
