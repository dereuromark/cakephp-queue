<?php
/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Queue\Shell\Task;

/**
 * A cascading QueueTask example.
 */
class QueueSuperExampleTask extends QueueTask implements AddInterface {

	/**
	 * Timeout for run, after which the Task is reassigned to a new worker.
	 *
	 * @var int
	 */
	public $timeout = 10;

	/**
	 * SuperExample add functionality.
	 * Will create another example job in the queue, which later will be executed using run();
	 *
	 * To invoke from CLI execute:
	 * - bin/cake queue add SuperExample
	 *
	 * @return void
	 */
	public function add() {
		$this->out('CakePHP Queue SuperExample task.');
		$this->hr();
		$this->out('This is a very superb example of a QueueTask.');
		$this->out('I will now add an example Job into the Queue.');
		$this->out('It will also create another Example job upon successful execution.');
		$this->out('This job will only produce some console output on the worker that it runs on.');
		$this->out(' ');
		$this->out('To run a Worker use:');
		$this->out('    bin/cake queue runworker');
		$this->out(' ');
		$this->out('You can find the sourcecode of this task in: ');
		$this->out(__FILE__);
		$this->out(' ');

		$this->QueuedJobs->createJob('SuperExample');
		$this->success('OK, job created, now run the worker');
	}

	/**
	 * SuperExample run function.
	 * This function is executed, when a worker is executing a task.
	 * The return parameter will determine, if the task will be marked completed, or be requeued.
	 *
	 * @param array $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		$this->hr();
		$this->out('CakePHP Queue SuperExample task.');

		// Lets create an Example task on successful execution
		$this->QueuedJobs->createJob('Example');
		$this->out('... New Example task has been scheduled.');

		$this->hr();
		$this->success(' -> Success, the SuperExample Job was run. <-');
	}

}
