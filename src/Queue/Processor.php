<?php
declare(strict_types=1);

namespace Queue\Queue;

use Cake\Console\CommandInterface;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Text;
use Psr\Log\LoggerInterface;
use Queue\Console\Io;
use Queue\Model\Entity\QueuedJob;
use Queue\Model\ProcessEndingException;
use Queue\Model\QueueException;
use Queue\Model\Table\QueuedJobsTable;
use Queue\Model\Table\QueueProcessesTable;
use RuntimeException;
use Throwable;

declare(ticks = 1);

/**
 * Main shell to init and run queue workers.
 */
class Processor {

	use LocatorAwareTrait;

	/**
	 * @var \Queue\Console\Io
	 */
	protected Io $io;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected LoggerInterface $logger;

	/**
	 * @var \Cake\Core\ContainerInterface|null
	 */
	protected ?ContainerInterface $container = null;

	/**
	 * @var array<string, array<string, mixed>>|null
	 */
	protected ?array $taskConf = null;

	/**
	 * @var int
	 */
	protected int $time = 0;

	/**
	 * @var bool
	 */
	protected bool $exit = false;

	/**
	 * @var string|null
	 */
	protected ?string $pid = null;

	/**
	 * @var \Queue\Model\Table\QueuedJobsTable
	 */
	protected QueuedJobsTable $QueuedJobs;

	/**
	 * @var \Queue\Model\Table\QueueProcessesTable
	 */
	protected QueueProcessesTable $QueueProcesses;

	/**
	 * @param \Queue\Console\Io $io
	 * @param \Psr\Log\LoggerInterface $logger
	 * @param \Cake\Core\ContainerInterface|null $container
	 */
	public function __construct(Io $io, LoggerInterface $logger, ?ContainerInterface $container = null) {
		$this->io = $io;
		$this->logger = $logger;
		$this->container = $container;

		$tableLocator = $this->getTableLocator();
		$this->QueuedJobs = $tableLocator->get('Queue.QueuedJobs');
		$this->QueueProcesses = $tableLocator->get('Queue.QueueProcesses');
	}

	/**
	 * @param array<string, mixed> $args
	 *
	 * @return int
	 */
	public function run(array $args): int {
		$config = $this->getConfig($args);

		try {
			$pid = $this->initPid(implode(' ', $_SERVER['argv']));
		} catch (PersistenceFailedException $exception) {
			$this->io->err($exception->getMessage());
			$limit = (int)Configure::read('Queue.maxworkers');
			if ($limit) {
				$this->io->out('Cannot start worker: Too many workers already/still running on this server (' . $limit . '/' . $limit . ')');
			}

			$this->QueueProcesses->cleanEndedProcesses();

			return CommandInterface::CODE_ERROR;
		}

		// Enable Garbage Collector (PHP >= 5.3)
		if (function_exists('gc_enable')) {
			gc_enable();
		}
		if (function_exists('pcntl_signal')) {
			pcntl_signal(SIGTERM, [&$this, 'exit']);
			pcntl_signal(SIGINT, [&$this, 'abort']);
			pcntl_signal(SIGTSTP, [&$this, 'abort']);
			pcntl_signal(SIGQUIT, [&$this, 'abort']);
			if (Configure::read('Queue.canInterruptSleep')) {
				// Defining a signal handler here will make the worker wake up
				// from its sleep() when SIGUSR1 is received. Since waking it
				// up is all we need, there is no further code to execute,
				// hence the empty function.
				pcntl_signal(SIGUSR1, function (): void {
				});
			}
		}
		$this->exit = false;

		$startTime = time();

		while (!$this->exit) {
			$this->setPhpTimeout();

			try {
				$this->updatePid($pid);
			} catch (RecordNotFoundException $exception) {
				// Manually killed
				$this->exit = true;

				continue;
			} catch (ProcessEndingException $exception) {
				// Soft killed, e.g. during deploy update
				$this->exit = true;

				continue;
			}

			if ($config['verbose']) {
				$this->log('run', $pid, false);
			}
			$this->io->out('[' . date('Y-m-d H:i:s') . '] Looking for Job ...');

			$queuedJob = $this->QueuedJobs->requestJob($this->getTaskConf(), $config['groups'], $config['types']);

			if ($queuedJob) {
				$this->runJob($queuedJob, $pid);
			} elseif (Configure::read('Queue.exitwhennothingtodo')) {
				$this->io->out('nothing to do, exiting.');
				$this->exit = true;
			} else {
				$this->io->out('nothing to do, sleeping.');
				sleep(Config::sleeptime());
			}

			// check if we are over the maximum runtime and end processing if so.
			if (Configure::readOrFail('Queue.workermaxruntime') && (time() - $startTime) >= Configure::readOrFail('Queue.workermaxruntime')) {
				$this->exit = true;
				$this->io->out('Reached runtime of ' . (time() - $startTime) . ' Seconds (Max ' . Configure::readOrFail('Queue.workermaxruntime') . '), terminating.');
			}
			if ($this->exit || mt_rand(0, 100) > 100 - (int)Config::gcprob()) {
				$this->io->out('Performing Old job cleanup.');
				$this->QueuedJobs->cleanOldJobs();
				$this->QueueProcesses->cleanEndedProcesses();
			}
			$this->io->hr();
		}

		$this->deletePid($pid);

		if ($config['verbose']) {
			$this->log('endworker', $pid);
		}

		return CommandInterface::CODE_SUCCESS;
	}

	/**
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 * @param string $pid
	 *
	 * @return void
	 */
	protected function runJob(QueuedJob $queuedJob, string $pid): void {
		$this->io->out('Running Job of type "' . $queuedJob->job_task . '"');
		$this->log('job ' . $queuedJob->job_task . ', id ' . $queuedJob->id, $pid, false);
		$taskName = $queuedJob->job_task;

		$return = $failureMessage = null;
		try {
			$this->time = time();

			$data = $queuedJob->data;
			$task = $this->loadTask($taskName);

            $this->QueueProcesses->update($pid, $queuedJob->id);

			$traits = class_uses($task);
			if ($this->container && $traits && in_array(ServicesTrait::class, $traits, true)) {
				/** @phpstan-ignore-next-line */
				$task->setContainer($this->container);
			}
			$task->run((array)$data, $queuedJob->id);
		} catch (Throwable $e) {
			$return = false;

			$failureMessage = get_class($e) . ': ' . $e->getMessage();
			if (!($e instanceof QueueException)) {
				$failureMessage .= "\n" . $e->getTraceAsString();
			}

			$this->logError($taskName . ' (job ' . $queuedJob->id . ')' . "\n" . $failureMessage, $pid);
		}

		$this->QueueProcesses->update($pid, NULL);

		if ($return === false) {
			$this->QueuedJobs->markJobFailed($queuedJob, $failureMessage);
			$failedStatus = $this->QueuedJobs->getFailedStatus($queuedJob, $this->getTaskConf());
			$this->log('job ' . $queuedJob->job_task . ', id ' . $queuedJob->id . ' failed and ' . $failedStatus, $pid);
			$this->io->out('Job did not finish, ' . $failedStatus . ' after try ' . $queuedJob->attempts . '.');

			return;
		}

		$this->QueuedJobs->markJobDone($queuedJob);
		$this->io->out('Job Finished.');
	}

	/**
	 * Timestamped log.
	 *
	 * @param string $message Log type
	 * @param string|null $pid PID of the process
	 * @param bool $addDetails
	 *
	 * @return void
	 */
	protected function log(string $message, ?string $pid = null, bool $addDetails = true): void {
		if (!Configure::read('Queue.log')) {
			return;
		}

		if ($addDetails) {
			$timeNeeded = $this->timeNeeded();
			$memoryUsage = $this->memoryUsage();
			$message .= ' [' . $timeNeeded . ', ' . $memoryUsage . ']';
		}

		if ($pid) {
			$message .= ' (pid ' . $pid . ')';
		}
		$this->logger->info($message, ['scope' => 'queue']);
	}

	/**
	 * @param string $message
	 * @param string|null $pid PID of the process
	 *
	 * @return void
	 */
	protected function logError(string $message, ?string $pid = null): void {
		$timeNeeded = $this->timeNeeded();
		$memoryUsage = $this->memoryUsage();
		$message .= ' [' . $timeNeeded . ', ' . $memoryUsage . ']';

		if ($pid) {
			$message .= ' (pid ' . $pid . ')';
		}
		$serverString = $this->QueueProcesses->buildServerString();
		if ($serverString) {
			$message .= ' {' . $serverString . '}';
		}

		$this->logger->error($message);
	}

	/**
	 * Returns a List of available QueueTasks and their individual configuration.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function getTaskConf(): array {
		if (!is_array($this->taskConf)) {
			$tasks = (new TaskFinder())->all();
			$this->taskConf = Config::taskConfig($tasks);
		}

		return $this->taskConf;
	}

	/**
	 * Signal handling to queue worker for clean shutdown
	 *
	 * @param int $signal
	 *
	 * @return void
	 */
	protected function exit(int $signal): void {
		$this->exit = true;
	}

	/**
	 * Signal handling for Ctrl+C
	 *
	 * @param int $signal
	 *
	 * @return void
	 */
	protected function abort(int $signal = 1): void {
		$this->deletePid($this->pid);

		exit($signal);
	}

	/**
     * Adds process to table and saves arguments
     * @param string $arguments
     *
	 * @return string
	 */
	protected function initPid(string $arguments = NULL): string {
		$pid = $this->retrievePid();
		$key = $this->QueuedJobs->key();
		$this->QueueProcesses->add($pid, $key, $arguments);

		$this->pid = $pid;

		return $pid;
	}

	/**
	 * @return string
	 */
	protected function retrievePid(): string {
		if (function_exists('posix_getpid')) {
			$pid = (string)posix_getpid();
		} else {
			$pid = $this->QueuedJobs->key();
		}

		return $pid;
	}

	/**
     * Touches process $pid and saves current $jobId
	 * @param string $pid
	 * @param int $jobId
	 * @return void
	 */
	protected function updatePid(string $pid, int $jobId = NULL): void {
		$this->QueueProcesses->update($pid, $jobId);
	}

	/**
	 * @return string Memory usage in MB.
	 */
	protected function memoryUsage(): string {
		$limit = ini_get('memory_limit');

		$used = number_format(memory_get_peak_usage(true) / (1024 * 1024), 0) . 'MB';
		if ($limit !== '-1') {
			$used .= '/' . $limit;
		}

		return $used;
	}

	/**
	 * @param string|null $pid
	 *
	 * @return void
	 */
	protected function deletePid(?string $pid): void {
		if (!$pid) {
			$pid = $this->pid;
		}
		if (!$pid) {
			return;
		}

		$this->QueueProcesses->remove($pid);
	}

	/**
	 * @return string
	 */
	protected function timeNeeded(): string {
		$diff = $this->time() - $this->time($this->time);
		$seconds = max($diff, 1);

		return $seconds . 's';
	}

	/**
	 * @param int|null $providedTime
	 *
	 * @return int
	 */
	protected function time(?int $providedTime = null): int {
		if ($providedTime) {
			return $providedTime;
		}

		return time();
	}

	/**
	 * @param string $param
	 *
	 * @return array<string>
	 */
	protected function stringToArray(string $param): array {
		if (!$param) {
			return [];
		}

		$array = Text::tokenize($param);

		return array_filter($array);
	}

	/**
	 * Makes sure accidental overriding isn't possible, uses workermaxruntime times 100 by default.
	 * If available, uses workertimeout config directly.
	 *
	 * @return void
	 */
	protected function setPhpTimeout(): void {
		$timeLimit = (int)Configure::readOrFail('Queue.workermaxruntime') * 100;
		if (Configure::read('Queue.workertimeout') !== null) {
			$timeLimit = (int)Configure::read('Queue.workertimeout');
		}

		set_time_limit($timeLimit);
	}

	/**
	 * @param array<string, mixed> $args
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(array $args): array {
		$config = [
			'groups' => [],
			'types' => [],
			'verbose' => false,
		];
		if (!empty($args['verbose'])) {
			$config['verbose'] = true;
		}
		if (!empty($args['group'])) {
			$config['groups'] = $this->stringToArray($args['group']);
		}
		if (!empty($args['type'])) {
			$config['types'] = $this->stringToArray($args['type']);
		}

		return $config;
	}

	/**
	 * @param string $taskName
	 *
	 * @return \Queue\Queue\TaskInterface
	 */
	protected function loadTask(string $taskName): TaskInterface {
		$className = $this->getTaskClass($taskName);
		/** @var \Queue\Queue\Task $task */
		$task = new $className($this->io, $this->logger);
		if (!$task instanceof TaskInterface) {
			throw new RuntimeException('Task must implement ' . TaskInterface::class);
		}

		return $task;
	}

	/**
	 * @psalm-return class-string<\Queue\Queue\Task>
	 *
	 * @param string $taskName
	 *
	 * @return string
	 */
	protected function getTaskClass(string $taskName): string {
		$taskConf = $this->getTaskConf();
		if (empty($taskConf[$taskName])) {
			throw new RuntimeException('No such task found: ' . $taskName);
		}

		return $taskConf[$taskName]['class'];
	}

}
