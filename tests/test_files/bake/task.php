<?php

namespace TestApp\Queue\Task;

use Queue\Queue\AddInterface;
use Queue\Queue\Task;

class FooBarBazTask extends Task implements AddInterface {

	/**
	 * @param array<string, mixed> $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
	}

	/**
	 * @param string|null $data Optional data for the task, make sure to "quote multi words"
	 *
	 * @return void
	 */
	public function add(?string $data): void {
		$this->QueuedJobs->createJob('FooBarBazTask');
	}

}
