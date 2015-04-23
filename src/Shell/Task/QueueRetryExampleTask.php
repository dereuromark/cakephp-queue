<?php
/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */
App::uses('QueueTask', 'Queue.Console/Command/Task');

/**
 * A Simple QueueTask example.
 *
 */
class QueueRetryExampleTask extends QueueTask {

	/**
	 * ZendStudio Codecomplete Hint
	 *
	 * @var QueuedTask
	 */
	public $QueuedTask;

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
	public $retries = 5;

	/**
	 * Stores any failure messages triggered during run()
	 *
	 * @var string
	 */
	public $failureMessage = '';

	/**
	 *  Constructs this Shell instance.
	 *
	 * @param ConsoleOutput $stdout A ConsoleOutput object for stdout.
	 * @param ConsoleOutput $stderr A ConsoleOutput object for stderr.
	 * @param ConsoleInput $stdin A ConsoleInput object for stdin.
	 */
	public function __construct($stdout = null, $stderr = null, $stdin = null) {
		parent::__construct($stdout, $stderr, $stdin);

		$this->file = TMP . 'task_retry.txt';
	}

	/**
	 * Example add functionality.
	 * Will create one example job in the queue, which later will be executed using run();
	 *
	 * @return void
	 */
	public function add() {
		$this->out('CakePHP Queue Retry Example task.');
		$this->hr();
		$this->out('This is a very simple example of a QueueTask and how retries work.');
		$this->out('I will now add an example Job into the Queue.');
		$this->out('This job will only produce some console output on the worker that it runs on.');
		$this->out(' ');
		$this->out('To run a Worker use:');
		$this->out('	cake Queue.Queue runworker');
		$this->out(' ');
		$this->out('You can find the sourcecode of this task in: ');
		$this->out(__FILE__);
		$this->out(' ');

		file_put_contents($this->file, '0');

		/*
		 * Adding a task of type 'example' with no additionally passed data
		 */
		if ($this->QueuedTask->createJob('RetryExample', null)) {
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
	 * @param array $data The array passed to QueuedTask->createJob()
	 * @param int $id The id of the QueuedTask
	 * @return bool Success
	 */
	public function run($data, $id = null) {
		$count = (int)file_get_contents($this->file);

		$this->hr();
		$this->out('CakePHP Queue Example task.');
		$this->hr();

		if ($count < 3) {
			$count++;
			file_put_contents($this->file, (string)$count);
			$this->out(' ->Sry, the Retry Example Job failed. Try again.<-');
			$this->out(' ');
			$this->out(' ');
			return false;
		}

		$this->out(' ->Success, the Retry Example Job was run.<-');
		$this->out(' ');
		$this->out(' ');

		unlink($this->file);
		return true;
	}
}
