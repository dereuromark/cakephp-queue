<?php
/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */

namespace Queue\Shell\Task;

/**
 * A Simple QueueTask example.
 */
class QueueSuperExampleTask extends QueueTask {

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
	public $retries = 1;

	/**
	 * SuperExample add functionality.
	 * Will create one example job in the queue, which later will be executed using run();
	 *
	 * @return void
	 */
	public function add() {
		$this->out('CakePHP Queue SuperExample task.');
		$this->hr();
		$this->out('This is a very superb example of a QueueTask.');
		$this->out('I will now add an example Job into the Queue.');
		$this->out('It will also fire a callback upon successful execution.');
		$this->out('This job will only produce some console output on the worker that it runs on.');
		$this->out(' ');
		$this->out('To run a Worker use:');
		$this->out('	bin/cake queue runworker');
		$this->out(' ');
		$this->out('You can find the sourcecode of this task in: ');
		$this->out(__FILE__);
		$this->out(' ');
		/*
		 * Adding a task of type 'example' with no additionally passed data
		 */
		if ($this->QueuedJobs->createJob('SuperExample', null)) {
			$this->out('OK, job created, now run the worker');
		} else {
			$this->err('Could not create Job');
		}
	}

	/**
	 * SuperExample run function.
	 * This function is executed, when a worker is executing a task.
	 * The return parameter will determine, if the task will be marked completed, or be requeued.
	 *
	 * @param array $data The array passed to QueuedTask->createJob()
	 * @param int $id The id of the QueuedTask
	 * @return bool Success
	 */
	public function run(array $data, $id) {
		$this->hr();
		$this->out('CakePHP Queue SuperExample task.');
		$this->hr();
		$this->out(' ->Success, the SuperExample Job was run.<-');
		$this->out(' ');
		$this->out(' ');

		// Lets create an Example task on successful execution
		$this->QueuedJobs->createJob('Example');

		return true;
	}

}
