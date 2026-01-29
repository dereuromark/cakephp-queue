<?php
declare(strict_types=1);

namespace Queue\Queue;

use Cake\Console\CommandInterface;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\Event;
use Cake\Event\EventManager;
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
use const SIGINT;
use const SIGQUIT;
use const SIGTERM;
use const SIGTSTP;
use const SIGUSR1;

declare(ticks=1);

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
	 * @var \Queue\Model\Entity\QueuedJob|null
	 */
	protected ?QueuedJob $currentJob = null;

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
			$pid = $this->initPid();
		} catch (PersistenceFailedException $exception) {
			$this->io->error($exception->getMessage());
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
			$this->setPhpTimeout($config['maxruntime']);

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

			$workerLifetime = Configure::read('Queue.workerLifetime') ?? Configure::read('Queue.workermaxruntime');
			if ($workerLifetime === null && $config['maxruntime'] === null) {
				throw new RuntimeException('Queue.workerLifetime (or deprecated workermaxruntime) config is required');
			}
			$maxRuntime = $config['maxruntime'] ?? (int)$workerLifetime;
			// check if we are over the maximum runtime and end processing if so.
			if ($maxRuntime > 0 && (time() - $startTime) >= $maxRuntime) {
				$this->exit = true;
				$this->io->out('Reached runtime of ' . (time() - $startTime) . ' Seconds (Max ' . $maxRuntime . '), terminating.');
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
		$this->currentJob = $queuedJob;
		$this->io->out('Running Job of type "' . $queuedJob->job_task . '"');
		$this->log('job ' . $queuedJob->job_task . ', id ' . $queuedJob->id, $pid, false);
		$taskName = $queuedJob->job_task;

		// Dispatch started event
		$event = new Event('Queue.Job.started', $this, [
			'job' => $queuedJob,
		]);
		EventManager::instance()->dispatch($event);

		$captureOutput = (bool)Configure::read('Queue.captureOutput');
		if ($captureOutput) {
			$this->io->enableOutputCapture();
		}

		$return = $failureMessage = null;
		try {
			$this->time = time();

			$data = $queuedJob->data;
			$task = $this->loadTask($taskName);
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

		$capturedOutput = null;
		if ($captureOutput) {
			$maxOutputSize = (int)(Configure::read('Queue.maxOutputSize') ?: 65536);
			$capturedOutput = $this->io->getOutputAsText($maxOutputSize);
			$this->io->disableOutputCapture();
		}

		if ($return === false) {
			$this->QueuedJobs->markJobFailed($queuedJob, $failureMessage, $capturedOutput);
			$failedStatus = $this->QueuedJobs->getFailedStatus($queuedJob, $this->getTaskConf());
			$this->log('job ' . $queuedJob->job_task . ', id ' . $queuedJob->id . ' failed and ' . $failedStatus, $pid);
			$this->io->out('Job did not finish, ' . $failedStatus . ' after try ' . $queuedJob->attempts . '.');

			// Dispatch failed event
			$event = new Event('Queue.Job.failed', $this, [
				'job' => $queuedJob,
				'failureMessage' => $failureMessage,
				'exception' => $e ?? null,
			]);
			EventManager::instance()->dispatch($event);

			// Dispatch event when job has exhausted all retries
			if ($failedStatus === 'aborted') {
				$event = new Event('Queue.Job.maxAttemptsExhausted', $this, [
					'job' => $queuedJob,
					'failureMessage' => $failureMessage,
				]);
				EventManager::instance()->dispatch($event);
			}

			return;
		}

		$this->QueuedJobs->markJobDone($queuedJob, $capturedOutput);

		// Dispatch completed event
		$event = new Event('Queue.Job.completed', $this, [
			'job' => $queuedJob,
		]);
		EventManager::instance()->dispatch($event);

		$this->io->out('Job Finished.');
		$this->currentJob = null;
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
		if ($this->currentJob) {
			$failureMessage = 'Worker process terminated by signal (SIGTERM) - job execution interrupted due to timeout or manual termination';
			$this->QueuedJobs->markJobFailed($this->currentJob, $failureMessage);
			$this->logError('Job ' . $this->currentJob->job_task . ' (id ' . $this->currentJob->id . ') failed due to worker termination', $this->pid);
			$this->io->out('Current job marked as failed due to worker termination.');
		}
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
	 * @return string
	 */
	protected function initPid(): string {
		$pid = $this->retrievePid();
		$key = $this->QueuedJobs->key();
		$this->QueueProcesses->add($pid, $key);

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
	 * @param string $pid
	 *
	 * @return void
	 */
	protected function updatePid(string $pid): void {
		$this->QueueProcesses->update($pid);
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
	 * Makes sure accidental overriding isn't possible, uses workermaxruntime times 2 by default.
	 * If available, uses workertimeout config directly.
	 *
	 * @param int|null $maxruntime Max runtime in seconds if set via CLI option.
	 *
	 * @return void
	 */
	protected function setPhpTimeout(?int $maxruntime): void {
		if ($maxruntime) {
			set_time_limit($maxruntime * 2);

			return;
		}

		// Check for new config name first, fall back to old name for backward compatibility
		$phpTimeout = Configure::read('Queue.workerPhpTimeout');
		if ($phpTimeout === null) {
			$phpTimeout = Configure::read('Queue.workertimeout');
			if ($phpTimeout !== null) {
				trigger_error(
					'Config key "Queue.workertimeout" is deprecated. Use "Queue.workerPhpTimeout" instead.',
					E_USER_DEPRECATED,
				);
			}
		}

		if ($phpTimeout !== null) {
			$timeLimit = (int)$phpTimeout;
		} else {
			// Default to workermaxruntime * 2 (or workerLifetime * 2 with new naming)
			$workerLifetime = Configure::read('Queue.workerLifetime') ?? Configure::read('Queue.workermaxruntime', 60);
			$timeLimit = (int)$workerLifetime * 2;
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
			'maxruntime' => null,
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
		if (isset($args['max-runtime']) && $args['max-runtime'] !== '') {
			$config['maxruntime'] = (int)$args['max-runtime'];
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
