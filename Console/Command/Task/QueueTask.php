<?php
/**
 * @author Andy Carter
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::uses('AppShell', 'Console/Command');

/**
 * Queue Task.
 *
 * Common Queue plugin tasks properties and methods to be extended by custom
 * tasks.
 */
class QueueTask extends AppShell {

/**
 * Adding the QueueTask Model
 *
 * @var array
 */
	public $uses = [
		'Queue.QueuedTask'
	];

/**
 * Timeout for run, after which the Task is reassigned to a new worker.
 *
 * @var int
 */
//	public $timeout = 120;

/**
 * Number of times a failed instance of this task should be restarted before giving up.
 *
 * @var int
 */
//	public $retries = 4;

/**
 * @var bool
 */
	public $autoUnserialize = true;

/**
 * Add functionality.
 *
 * @return void
 */
	public function add() {

		return;

	}

/**
 * Run function.
 * This function is executed, when a worker is executing a task.
 * The return parameter will determine, if the task will be marked completed, or be requeued.
 *
 * @param array $data The array passed to QueuedTask->createJob()
 * @return bool Success
 */
	public function run($data) {

		return true;

	}

}
