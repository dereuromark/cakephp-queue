<?php
/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Queue\Shell\Task;

use RuntimeException;

/**
 * A retry QueueTask example.
 */
class QueueRetryExampleTask extends QueueTask implements AddInterface {

	/**
	 * Timeout for run, after which the Task is reassigned to a new worker.
	 *
	 * @var int
	 */
	public $timeout = 10;

	/**
	 * Number of times a failed instance of this task should be restarted before giving up.
	 *
	 * @var int
	 */
	public $retries = 4;

	/**
	 * @var string
	 */
	protected static $file = TMP . 'task_retry.txt';

	/**
	 * This is only for demo/testing purposes.
	 *
	 * @throws \RuntimeException
	 *
	 * @return bool
	 */
	public static function init(): bool {
		if (file_exists(static::$file)) {
			return false;
		}

		file_put_contents(static::$file, '0');

		if (!file_exists(static::$file)) {
			throw new RuntimeException('Cannot create necessary test file: ' . static::$file);
		}

		return true;
	}

	/**
	 * Example add functionality.
	 * Will create one example job in the queue, which later will be executed using run();
	 *
	 * To invoke from CLI execute:
	 * - bin/cake queue add RetryExample
	 *
	 * @return void
	 */
	public function add() {
		$this->out('CakePHP Queue RetryExample task.');
		$this->hr();
		$this->out('This is a very simple example of a QueueTask and how retries work.');
		$this->out('I will now add an example Job into the Queue.');
		$this->out('This job will only produce some console output on the worker that it runs on.');
		$this->out(' ');
		$this->out('To run a Worker use:');
		$this->out('    bin/cake queue runworker');
		$this->out(' ');
		$this->out('You can find the sourcecode of this task in: ');
		$this->out(__FILE__);
		$this->out(' ');

		$init = static::init();
		if (!$init) {
			$this->warn('File seems to already exist. Make sure you run this task standalone. You cannot run it multiple times in parallel!');
		}

		$this->QueuedJobs->createJob('RetryExample');
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
	 */
	public function run(array $data, int $jobId): void {
		if (!file_exists(static::$file)) {
			$this->abort(' -> No demo file found. Aborting. <-');
		}

		$count = (int)file_get_contents(static::$file);

		$this->hr();
		$this->out('CakePHP Queue RetryExample task.');
		$this->hr();

		// Let's fake 3 fails before it actually runs successfully
		if ($count < 3) {
			$count++;
			file_put_contents(static::$file, (string)$count);
			$this->abort(' -> Sry, the RetryExample Job failed. Try again. <-');
		}

		unlink(static::$file);
		$this->success(' -> Success, the RetryExample Job was run. <-');
	}

}
