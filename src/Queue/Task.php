<?php
/**
 * @author Andy Carter
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Queue\Queue;

use Cake\Console\ConsoleIo;
use Cake\ORM\Locator\LocatorAwareTrait;
use Psr\Log\LoggerInterface;
use Queue\Console\Io;

/**
 * Queue Task.
 *
 * Common Queue plugin tasks properties and methods to be extended by custom
 * tasks.
 */
abstract class Task implements TaskInterface {

	use LocatorAwareTrait;

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
	 * @var \Queue\Console\Io
	 */
	protected $io;

	/**
	 * @var \Psr\Log\LoggerInterface|null
	 */
	protected $logger;

	/**
	 * @param \Queue\Console\Io|null $io IO
	 * @param \Psr\Log\LoggerInterface|null $logger
	 */
	public function __construct(?Io $io = null, ?LoggerInterface $logger = null) {
		$this->io = $io ?: new Io(new ConsoleIo());
		$this->logger = $logger;

		$tableLocator = $this->getTableLocator();

		/** @var \Queue\Model\Table\QueuedJobsTable $QueuedJobs */
		$QueuedJobs = $tableLocator->get($this->queueModelClass);
		if (isset($this->modelClass)) {
			/** @var \Queue\Model\Table\QueuedJobsTable $QueuedJobs */
			$QueuedJobs = $tableLocator->get($this->modelClass);
		}
		$this->QueuedJobs = $QueuedJobs;
	}

	/**
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public static function taskName(): string {
		$class = static::class;

		return Config::taskName($class);
	}

}
