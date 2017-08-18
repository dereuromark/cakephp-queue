<?php

namespace Queue\Shell\Task;

/**
 * A Simple QueueTask example that runs for a while and updates the progress field.
 */
class QueueProgressExampleTask extends QueueTask {

	/**
	 * Timeout for run, after which the Task is reassigned to a new worker.
	 *
	 * @var int
	 */
	public $timeout = 120;

	/**
	 * Number of times a failed instance of this task should be restarted before giving up.
	 *
	 * @var int
	 */
	public $retries = 1;

	/**
	 * Example add functionality.
	 * Will create one example job in the queue, which later will be executed using run();
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
		$this->out('	bin/cake queue runworker');
		$this->out(' ');
		$this->out('You can find the sourcecode of this task in:');
		$this->out(__FILE__);
		$this->out(' ');
		/*
		 * Adding a task of type 'example' with no additionally passed data
		 */
		$data = [
			'duration' => 2 * MINUTE
		];
		if ($this->QueuedJobs->createJob('ProgressExample', $data)) {
			$this->out('OK, job created, now run the worker');
		} else {
			$this->err('Could not create Job');
		}
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
	 * @return bool Success
	 */
	public function run(array $data, $jobId) {
		$this->hr();
		$this->out('CakePHP Queue ProgressExample task.');
		$seconds = !empty($data['duration']) ? (int)$data['duration'] : 2 * MINUTE;

		$this->out('A total of ' . $seconds . ' seconds need to pass...');
		for ($i = 0; $i < $seconds; $i++) {
			sleep(1);
			$this->QueuedJobs->updateProgress($jobId, ($i + 1) / $seconds);
		}
		$this->QueuedJobs->updateProgress($jobId, 1);

		$this->hr();
		$this->out(' ->Success, the ProgressExample Job was run.<-');
		$this->out(' ');
		$this->out(' ');
		return true;
	}

}
