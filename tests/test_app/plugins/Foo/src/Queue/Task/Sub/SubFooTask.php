<?php
declare(strict_types=1);

namespace Foo\Queue\Task\Sub;

use Queue\Queue\Task;

class SubFooTask extends Task {

	/**
	 * Timeout for run, after which the Task is reassigned to a new worker.
	 */
	public ?int $timeout = 10;

	/**
	 * Number of times a failed instance of this task should be restarted before giving up.
	 */
	public ?int $retries = 1;

	/**
	 * Example run function.
	 * This function is executed, when a worker is executing a task.
	 * The return parameter will determine, if the task will be marked completed, or be requeued.
	 *
	 * @param array<string, mixed> $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
	}

}
