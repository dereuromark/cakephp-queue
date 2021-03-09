<?php

namespace Queue\Shell\Task;

/**
 * Any task needs to at least implement run().
 * The add() is mainly only for CLI adding purposes and optional.
 *
 * Either throw an exception with an error message, or use $this->abort('My message'); to fail a job.
 *
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
interface QueueTaskInterface {

	/**
	 * Main execution of the task.
	 *
	 * @param array $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 * @return void
	 */
	public function run(array $data, int $jobId): void;

}
