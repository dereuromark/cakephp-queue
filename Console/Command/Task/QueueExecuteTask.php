<?php

/**
 * @author MGriesbach@gmail.com
 * @package QueuePlugin
 * @subpackage QueuePlugin.Tasks
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */

/**
 * Execute a Local command on the server.
 *
 */
class QueueExecuteTask extends AppShell {
	/**
	 * Adding the QueueTask Model
	 *
	 * @var array
	 */
	public $uses = array(
		'Queue.QueuedTask'
	);

	/**
	 * ZendStudio Codecomplete Hint
	 *
	 * @var QueuedTask
	 */
	public $QueuedTask;

	/**
	 * Timeout for run, after which the Task is reassigned to a new worker.
	 *
	 * @var integer
	 */
	public $timeout = 0;
	/**
	 * Number of times a failed instance of this task should be restarted before giving up.
	 *
	 * @var integer
	 */
	public $retries = 0;
	/**
	 * Stores any failure messages triggered during run()
	 *
	 * @var string
	 */
	public $failureMessage = '';

	/**
	 * Add functionality.
	 * Will create one example job in the queue, which later will be executed using run();
	 */
	public function add() {
		$this->out('CakePHP Queue Execute task.');
		$this->hr();
		if (count($this->args) < 2) {
			$this->out('This will run an shell command on the Server.');
			$this->out('The task is mainly intended to serve as a kind of buffer for programm calls from a cakephp application.');
			$this->out(' ');
			$this->out('Call like this:');
			$this->out('	cake queue add execute *command* *param1* *param2* ...');
			$this->out(' ');
		} else {

			$data = array(
				'command' => $this->args[1],
				'params' => array_slice($this->args, 2)
			);
			if ($this->QueuedTask->createJob('Execute', $data)) {
				$this->out('Job created');
			} else {
				$this->err('Could not create Job');
			}

		}
	}

	/**
	 * Run function.
	 * This function is executed, when a worker is executing a task.
	 * The return parameter will determine, if the task will be marked completed, or be requeued.
	 *
	 * @param array $data the array passed to QueuedTask->createJob()
	 * @return bool Success
	 */
	public function run($data) {
		$command = escapeshellcmd($data['command']) . ' ' . implode(' ', $data['params']);
		$this->out('Executing: ' . $command);
		exec($command, $output, $status);
		$this->out(' ');
		$this->out($output);
		return (!$status);
	}
}
