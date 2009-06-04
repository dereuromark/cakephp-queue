<?php

/**
 * @author MGriesbach@gmail.com
 * @package QueuePlugin
 * @subpackage QueuePlugin.Tasks
 */

/**
 * A Simple QueueTask example.
 *
 */
class queueExampleTask extends Shell {
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
	 * Example add functionality.
	 * Will create one example job in the queue, which later will be executed using run();
	 */
	public function add() {
		$this->out('CakePHP Queue Example task.');
		$this->hr();
		$this->out('This is a very simple example of a queueTask.');
		$this->out('I will now add an example Job into the Queue.');
		$this->out('This job will only produce some console output on the worker that it runs on.');
		$this->out(' ');
		$this->out('To run a Worker use:');
		$this->out('	cake queue runworker');
		$this->out(' ');
		$this->out('You can find the sourcecode of this task in: ');
		$this->out(__FILE__);
		$this->out(' ');
		/**
		 * Adding a task of type 'example' with no additionally passed data
		 */
		if ($this->QueuedTask->createJob('example', null)) {
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
	 * @param array $data the array passed to QueuedTask->createJob()
	 * @return bool Success
	 */
	public function run($data) {
		$this->hr();
		$this->out('CakePHP Queue Example task.');
		$this->hr();
		$this->out(' ->Success, the Example Job was run.<-');
		$this->out(' ');
		$this->out(' ');
		return true;
	}
}
?>