<?php
/**
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Queue\Shell\Task;

/**
 * A Simple QueueTask example.
 */
class QueueMonitorExampleTask extends QueueTask implements AddInterface {

	/**
	 * Timeout for run, after which the Task is reassigned to a new worker.
	 *
	 * @var int
	 */
	public $timeout = 10;

	/**
	 * MonitorExample add functionality.
	 * Will create one example job in the queue, which later will be executed using run();
	 *
	 * To invoke from CLI execute:
	 * - bin/cake queue add MonitorExample
	 *
	 * @return void
	 */
	public function add() {
		$this->out('CakePHP Queue MonitorExample task.');
		$this->hr();
		$this->out('This is an example of doing some server monitor tasks and logging.');
		$this->out('This job will only produce some console output on the worker that it runs on.');
		$this->out(' ');
		$this->out('To run a Worker use:');
		$this->out('    bin/cake queue runworker');
		$this->out(' ');
		$this->out('You can find the sourcecode of this task in: ');
		$this->out(__FILE__);
		$this->out(' ');

		$this->QueuedJobs->createJob('MonitorExample');
		$this->success('OK, job created, now run the worker');
	}

	/**
	 * MonitorExample run function.
	 * This function is executed, when a worker is executing a task.
	 * The return parameter will determine, if the task will be marked completed, or be requeued.
	 *
	 * @param array $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		$this->hr();
		$this->out('CakePHP Queue MonitorExample task.');
		$this->hr();

		$this->doMonitoring();

		$this->success(' -> Success, the MonitorExample Job was run. <-');
	}

	/**
	 * @return void
	 */
	protected function doMonitoring() {
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
	 * @return string[]
	 */
	protected function getSystemMemInfo() {
		$data = explode("\n", file_get_contents('/proc/meminfo'));
		$meminfo = [];
		foreach ($data as $line) {
			if (strpos($line, ':') === false) {
				continue;
			}
			list($key, $val) = explode(':', $line);
			$meminfo[$key] = trim($val);
		}
		return $meminfo;
	}

}
