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
use RuntimeException;

/**
 * A retry QueueTask example.
 */
class RetryExampleTask extends Task implements AddInterface, AddFromBackendInterface {

	/**
	 * Timeout for run, after which the Task is reassigned to a new worker.
	 */
	public ?int $timeout = 10;

	/**
	 * Number of times a failed instance of this task should be restarted before giving up.
	 */
	public ?int $retries = 4;

	/**
	 * @var string
	 */
	protected static string $file = TMP . 'task_retry.txt';

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
	 * - bin/cake queue add Queue.RetryExample
	 *
	 * @param string|null $data
	 *
	 * @return void
	 */
	public function add(?string $data): void {
		$this->io->out('CakePHP Queue RetryExample task.');
		$this->io->hr();
		$this->io->out('This is a very simple example of a QueueTask and how retries work.');
		$this->io->out('I will now add an example Job into the Queue.');
		$this->io->out('This job will only produce some console output on the worker that it runs on.');
		$this->io->out(' ');
		$this->io->out('To run a Worker use:');
		$this->io->out('    bin/cake queue run');
		$this->io->out(' ');
		$this->io->out('You can find the sourcecode of this task in: ');
		$this->io->out(__FILE__);
		$this->io->out(' ');

		$init = static::init();
		if (!$init) {
			$this->io->warn('File seems to already exist. Make sure you run this task standalone. You cannot run it multiple times in parallel!');
		}

		$this->QueuedJobs->createJob('Queue.RetryExample');
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
		if (!file_exists(static::$file)) {
			$this->io->abort(' -> No demo file found. Aborting. <-');
		}

		$count = (int)file_get_contents(static::$file);

		$this->io->hr();
		$this->io->out('CakePHP Queue RetryExample task.');
		$this->io->hr();

		// Let's fake 3 fails before it actually runs successfully
		if ($count < 3) {
			$count++;
			file_put_contents(static::$file, (string)$count);
			$this->io->abort(' -> Sry, the RetryExample Job failed. Try again. <-');
		}

		unlink(static::$file);
		$this->io->success(' -> Success, the RetryExample Job was run. <-');
	}

}
