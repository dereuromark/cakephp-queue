<?php

namespace Queue\Shell\Task;

/**
 * A Simple QueueTask example that runs for a while and updates the progress field.
 */
class QueueProgressExampleTask extends QueueTask implements AddInterface {

	/**
	 * Timeout for run, after which the Task is reassigned to a new worker.
	 *
	 * @var int
	 */
	public $timeout = 120;

	/**
	 * Example add functionality.
	 * Will create one example job in the queue, which later will be executed using run();
	 *
	 * To invoke from CLI execute:
	 * - bin/cake queue add ProgressExample
	 *
	 * @return void
	 */
	public function add() {
		$this->out('CakePHP Queue ProgressExample task.');
		$this->hr();
		$this->out('This is a very simple but long running example of a QueueTask.');
		$this->out('I will now add the Job into the Queue.');
		$this->out('This job will need at least 2 minutes to complete.');
		$this->out(' ');
		$this->out('To run a Worker use:');
		$this->out('    bin/cake queue runworker');
		$this->out(' ');
		$this->out('You can find the sourcecode of this task in:');
		$this->out(__FILE__);
		$this->out(' ');

		$data = [
			'duration' => 2 * MINUTE,
		];
		$this->QueuedJobs->createJob('ProgressExample', $data);
		$this->success('OK, job created, now run the worker');
	}

	/**
	 * Example run function.
	 * This function is executed, when a worker is executing a task.
	 * The return parameter will determine, if the task will be marked completed, or be requeued.
	 *
	 * Defaults to 120 seconds
	 *
	 * @param array $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		$this->hr();
		$this->out('CakePHP Queue ProgressExample task.');
		$seconds = !empty($data['duration']) ? (int)$data['duration'] : 2 * MINUTE;

		$this->out('A total of ' . $seconds . ' seconds need to pass...');
		for ($i = 0; $i < $seconds; $i++) {
			sleep(1);
			$this->QueuedJobs->updateProgress($jobId, ($i + 1) / $seconds, 'Status Test ' . ($i + 1) . 's');
		}
		$this->QueuedJobs->updateProgress($jobId, 1, 'Status Test Done');

		$this->hr();
		$this->success(' -> Success, the ProgressExample Job was run. <-');
	}

}
