<?php
declare(strict_types=1);

/**
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Queue\Queue\Task;

use Cake\Log\LogTrait;
use Queue\Queue\AddFromBackendInterface;
use Queue\Queue\AddInterface;
use Queue\Queue\Task;

/**
 * A Simple QueueTask example.
 */
class MonitorExampleTask extends Task implements AddInterface, AddFromBackendInterface {

	use LogTrait;

	/**
	 * Timeout for run, after which the Task is reassigned to a new worker.
	 */
	public ?int $timeout = 10;

	/**
	 * MonitorExample add functionality.
	 * Will create one example job in the queue, which later will be executed using run();
	 *
	 * To invoke from CLI execute:
	 * - bin/cake queue add Queue.MonitorExample
	 *
	 * @param string|null $data
	 *
	 * @return void
	 */
	public function add(?string $data): void {
		$this->io->out('CakePHP Queue MonitorExample task.');
		$this->io->hr();
		$this->io->out('This is an example of doing some server monitor tasks and logging.');
		$this->io->out('This job will only produce some console output on the worker that it runs on.');
		$this->io->out(' ');
		$this->io->out('To run a Worker use:');
		$this->io->out('    bin/cake queue run');
		$this->io->out(' ');
		$this->io->out('You can find the sourcecode of this task in: ');
		$this->io->out(__FILE__);
		$this->io->out(' ');

		$this->QueuedJobs->createJob('Queue.MonitorExample');
		$this->io->success('OK, job created, now run the worker');
	}

	/**
	 * MonitorExample run function.
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
		$this->io->out('CakePHP Queue MonitorExample task.');
		$this->io->hr();

		$this->doMonitoring();

		$this->io->success(' -> Success, the MonitorExample Job was run. <-');
	}

	/**
	 * @return void
	 */
	protected function doMonitoring(): void {
		$memory = $this->getSystemMemInfo();

		$array = [
			'[PHP] ' . PHP_VERSION,
			'[PHP Memory Limit] ' . ini_get('memory_limit'),
			'[Server Memory] Total: ' . $memory['MemTotal'] . ', Free: ' . $memory['MemFree'],
		];

		$message = implode(PHP_EOL, $array);
		$this->log($message, 'info');
	}

	/**
	 * @return array<string>
	 */
	protected function getSystemMemInfo(): array {
		$data = explode("\n", file_get_contents('/proc/meminfo') ?: '');
		$meminfo = [];
		foreach ($data as $line) {
			if (strpos($line, ':') === false) {
				continue;
			}
			[$key, $val] = explode(':', $line);
			$meminfo[$key] = trim($val);
		}

		return $meminfo;
	}

}
