<?php
/**
 * @author Andy Carter
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Queue\Shell\Task;

use Cake\Console\ConsoleIo;
use Cake\Console\Shell;
use InvalidArgumentException;

/**
 * Queue Task.
 *
 * Common Queue plugin tasks properties and methods to be extended by custom
 * tasks.
 */
abstract class QueueTask extends Shell implements QueueTaskInterface {

	/**
	 * @var string
	 */
	public $queueModelClass = 'Queue.QueuedJobs';

	/**
	 * @var \Queue\Model\Table\QueuedJobsTable
	 */
	public $QueuedJobs;

	/**
	 * Timeout for run, after which the Task is reassigned to a new worker.
	 *
	 * @var int
	 */
	public $timeout = 120;

	/**
	 * Number of times a failed instance of this task should be restarted before giving up.
	 *
	 * @var int
	 */
	public $retries = 1;

	/**
	 * @param \Cake\Console\ConsoleIo|null $io IO
	 */
	public function __construct(ConsoleIo $io = null) {
		parent::__construct($io);

		$this->loadModel($this->queueModelClass);
	}

	/**
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	protected function queueTaskName() {
		$class = get_class($this);

		preg_match('#\\\\Queue(.+)Task$#', $class, $matches);
		if (!$matches) {
			throw new InvalidArgumentException('Invalid class name: ' . $class);
		}

		return $matches[1];
	}

}
