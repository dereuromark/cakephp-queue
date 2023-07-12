<?php
declare(strict_types=1);

/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Queue\Queue\Task;

use Cake\Console\CommandInterface;
use Cake\Console\ConsoleIo;
use Cake\Log\LogTrait;
use Queue\Model\QueueException;
use Queue\Queue\AddInterface;
use Queue\Queue\Task;

/**
 * Execute a Local command on the server.
 *
 * @property \Queue\Model\Table\QueueProcessesTable $QueueProcesses
 */
class ExecuteTask extends Task implements AddInterface {

	use LogTrait;

	/**
	 * Add functionality.
	 * Will create one example job in the queue, which later will be executed using run();
	 *
	 * @param string|null $data
	 *
	 * @return void
	 */
	public function add(?string $data): void {
		$this->io->out('CakePHP Queue Execute task.');
		$this->io->hr();
		if (!$data) {
			$this->io->out('This will run an shell command on the Server.');
			$this->io->out('The task is mainly intended to serve as a kind of buffer for program calls from a CakePHP application.');
			$this->io->out(' ');
			$this->io->out('Call like this:');
			$this->io->out('    bin/cake queue add Execute "*command* *param1* *param2*" ...');
			$this->io->out(' ');
			$this->io->out('For commands with spaces use " around it. E.g. `bin/cake queue add Execute "sleep 10s"`.');
			$this->io->out(' ');

			return;
		}

		$command = $data;
		$params = null;
		if (strpos($data, ' ') !== false) {
			[$command, $params] = explode(' ', $data, 2);
		}

		$data = [
			'command' => $command,
			'params' => $params ? [$params] : [],
		];

		$this->QueuedJobs->createJob('Queue.Execute', $data);
		$this->io->success('OK, job created, now run the worker');
	}

	/**
	 * Run function.
	 * This function is executed, when a worker is executing a task.
	 * The return parameter will determine, if the task will be marked completed, or be requeued.
	 *
	 * @param array<string, mixed> $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 *
	 * @throws \Queue\Model\QueueException
	 *
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		$data += [
			'command' => null,
			'params' => [],
			'redirect' => true,
			'escape' => true,
			'log' => false,
			'accepted' => [CommandInterface::CODE_SUCCESS],
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

		$this->io->out('Executing: `' . $command . '`');

		if ($data['redirect']) {
			$command .= ' 2>&1';
		}

		exec($command, $output, $exitCode);
		$this->io->nl();
		$this->io->out($output);

		if ($data['log']) {
			$queueProcesses = $this->getTableLocator()->get('Queue.QueueProcesses');
			$server = $queueProcesses->buildServerString();
			$this->log($server . ': `' . $command . '` exits with `' . $exitCode . '` and returns `' . print_r($output, true) . '`' . PHP_EOL . 'Data : ' . print_r($data, true), 'info');
		}

		$acceptedReturnCodes = $data['accepted'];
		$success = !$acceptedReturnCodes || in_array($exitCode, $acceptedReturnCodes, true);
		if (!$success) {
			$this->io->err('Error (code ' . $exitCode . ')', ConsoleIo::VERBOSE);
		} else {
			$this->io->success('Success (code ' . $exitCode . ')', ConsoleIo::VERBOSE);
		}

		if (!$success) {
			throw new QueueException('Failed with error code ' . $exitCode . ': `' . $command . '`');
		}
	}

}
