<?php
/**
 * @author Andy Carter
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Queue\Shell\Task;

use Cake\Console\ConsoleIo;
use Cake\Console\Shell;
use Cake\ORM\Locator\LocatorInterface;
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
	 * Timeout in seconds, after which the Task is reassigned to a new worker
	 * if not finished successfully.
	 * This should be high enough that it cannot still be running on a zombie worker (>> 2x).
	 * Defaults to Config::defaultworkertimeout().
	 *
	 * @var int|null
	 */
	public $timeout;

	/**
	 * Number of times a failed instance of this task should be restarted before giving up.
	 * Defaults to Config::defaultworkerretries().
	 *
	 * @var int|null
	 */
	public $retries;

	/**
	 * Rate limiting per worker in seconds.
	 * Activate this if you want to stretch the processing of a specific task per worker.
	 *
	 * @var int
	 */
	public $rate = 0;

	/**
	 * Activate this if you want cost management per server to avoid server overloading.
	 *
	 * Expensive tasks (CPU, memory, ...) can have 1...100 points here, with higher points
	 * preventing a similar cost intensive task to be fetched on the same server in parallel.
	 * Smaller ones can easily still be processed on the same server if some an expensive one is running.
	 *
	 * @var int
	 */
	public $costs = 0;

	/**
	 * Set to true if you want to make sure this specific task is never run in parallel, neither
	 * on the same server, nor any other server. Any worker running will not fetch this task, if any
	 * job here is already in progress.
	 *
	 * @var bool
	 */
	public $unique = false;

	/**
	 * @param \Cake\Console\ConsoleIo|null $io IO
	 * @param \Cake\ORM\Locator\LocatorInterface|null $locator
	 */
	public function __construct(ConsoleIo $io = null, ?LocatorInterface $locator = null) {
		parent::__construct($io, $locator);

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
