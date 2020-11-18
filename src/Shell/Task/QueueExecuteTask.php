<?php
/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Queue\Shell\Task;

use Queue\Model\QueueException;

/**
 * Execute a Local command on the server.
 *
 * @property \Queue\Model\Table\QueueProcessesTable $QueueProcesses
 */
class QueueExecuteTask extends QueueTask implements AddInterface {

	/**
	 * Add functionality.
	 * Will create one example job in the queue, which later will be executed using run();
	 *
	 * @return void
	 */
	public function add() {
		$this->out('CakePHP Queue Execute task.');
		$this->hr();
		if (count($this->args) < 2) {
			$this->out('This will run an shell command on the Server.');
			$this->out('The task is mainly intended to serve as a kind of buffer for programm calls from a CakePHP application.');
			$this->out(' ');
			$this->out('Call like this:');
			$this->out('    bin/cake queue add Execute *command* *param1* *param2* ...');
			$this->out(' ');
			$this->out('For commands with spaces use " around it. E.g. `bin/cake queue add Execute "sleep 10s"`.');
			$this->out(' ');

			return;
		}

		$data = [
			'command' => $this->args[1],
			'params' => array_slice($this->args, 2),
		];

		$this->QueuedJobs->createJob('Execute', $data);
		$this->success('OK, job created, now run the worker');
	}

	/**
	 * Run function.
	 * This function is executed, when a worker is executing a task.
	 * The return parameter will determine, if the task will be marked completed, or be requeued.
	 *
	 * @param array $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 * @throws \Queue\Model\QueueException
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		$data += [
			'command' => null,
			'params' => [],
			'redirect' => true,
			'escape' => true,
			'log' => false,
			'accepted' => [static::CODE_SUCCESS],
		];

		$command = $data['command'];
		if ($data['escape']) {
			$command = escapeshellcmd($data['command']);
		}

		if ($data['params']) {
			$params = $data['params'];
			if ($data['escape']) {
				foreach ($params as $key => $value) {
					$params[$key] = escapeshellcmd($value);
				}
			}
			$command .= ' ' . implode(' ', $params);
		}

		$this->out('Executing: `' . $command . '`');

		if ($data['redirect']) {
			$command .= ' 2>&1';
		}

		exec($command, $output, $exitCode);
		$this->nl();
		$this->out($output);

		if ($data['log']) {
			$this->loadModel('Queue.QueueProcesses');
			$server = $this->QueueProcesses->buildServerString();
			$this->log($server . ': `' . $command . '` exits with `' . $exitCode .'` and returns `' . print_r($output, true) . '`: ' . print_r($data, true), 'info');
		}

		$acceptedReturnCodes = $data['accepted'];
		$success = !$acceptedReturnCodes || in_array($exitCode, $acceptedReturnCodes, true);
		if (!$success) {
			$this->err('Error (code ' . $exitCode . ')', static::VERBOSE);
		} else {
			$this->success('Success (code ' . $exitCode . ')', static::VERBOSE);
		}

		if (!$success) {
			throw new QueueException('Failed with error code ' . $exitCode . ': `' . $command . '`');
		}
	}

}
