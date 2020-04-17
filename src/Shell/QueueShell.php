<?php

namespace Queue\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\FrozenTime;
use Cake\I18n\Number;
use Cake\Log\Log;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Queue\Model\Entity\QueuedJob;
use Queue\Model\ProcessEndingException;
use Queue\Model\QueueException;
use Queue\Queue\Config;
use Queue\Queue\TaskFinder;
use Queue\Shell\Task\AddInterface;
use Queue\Shell\Task\QueueTaskInterface;
use RuntimeException;
use Throwable;

declare(ticks = 1);

/**
 * Main shell to init and run queue workers.
 *
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @property \Queue\Model\Table\QueuedJobsTable $QueuedJobs
 * @property \Queue\Model\Table\QueueProcessesTable $QueueProcesses
 */
class QueueShell extends Shell {

	/**
	 * @var string
	 */
	protected $modelClass = 'Queue.QueuedJobs';

	/**
	 * @var array|null
	 */
	protected $_taskConf;

	/**
	 * @var int
	 */
	protected $_time = 0;

	/**
	 * @var bool
	 */
	protected $_exit = false;

	/**
	 * @var string|null
	 */
	protected $_pid;

	/**
	 * Overwrite shell initialize to dynamically load all Queue Related Tasks.
	 *
	 * @return void
	 */
	public function initialize(): void {
		$taskFinder = new TaskFinder();
		$this->tasks = $taskFinder->allAppAndPluginTasks();

		parent::initialize();

		$this->loadModel('Queue.QueueProcesses');
	}

	/**
	 * @return void
	 */
	public function startup(): void {
		if ($this->param('quiet')) {
			$this->interactive = false;
		}

		parent::startup();
	}

	/**
	 * @return string
	 */
	public function _getDescription() {
		$tasks = [];
		foreach ($this->taskNames as $loadedTask) {
			$name = $this->_taskName($loadedTask);

			$tasks[$name] = "\t" . '* ' . $name;
		}
		ksort($tasks);

		$tasks = implode(PHP_EOL, $tasks);

		$text = <<<TEXT
Simple and minimalistic job queue (or deferred-task) system.

Available Tasks:
$tasks
TEXT;
		return $text;
	}

	/**
	 * Look for a Queue Task of hte passed name and try to call add() on it.
	 * A QueueTask may provide an add function to enable the user to create new jobs via commandline.
	 *
	 * @return void
	 */
	public function add() {
		if (count($this->args) < 1) {
			$this->out('Please call like this:');
			$this->out('    bin/cake queue add <taskname>');
			$this->_displayAvailableTasks();

			return;
		}

		$name = Inflector::camelize($this->args[0]);
		if (in_array('Queue' . $name, $this->taskNames, true)) {
			/** @var \Queue\Shell\Task\QueueTask|\Queue\Shell\Task\AddInterface $task */
			$task = $this->{'Queue' . $name};
			if (!($task instanceof AddInterface)) {
				$this->abort('This task does not support adding via CLI call');
			}
			$task->add();
		} else {
			$this->out('Error: Task not found: ' . $name);
			$this->_displayAvailableTasks();
		}
	}

	/**
	 * Output the task without Queue or Task
	 * example: QueueImageTask becomes Image on display
	 *
	 * @param string $task Task name
	 * @return string Cleaned task name
	 */
	protected function _taskName($task) {
		if (strpos($task, 'Queue') === 0) {
			return substr($task, 5);
		}
		return $task;
	}

	/**
	 * Run a QueueWorker loop.
	 * Runs a Queue Worker process which will try to find unassigned jobs in the queue
	 * which it may run and try to fetch and execute them.
	 *
	 * @return int
	 */
	public function runworker() {
		try {
			$pid = $this->_initPid();
		} catch (PersistenceFailedException $exception) {
			$this->err($exception->getMessage());
			$limit = (int)Configure::read('Queue.maxworkers');
			if ($limit) {
				$this->out('Cannot start worker: Too many workers already/still running on this server (' . $limit . '/' . $limit . ')');
			}

			$this->QueueProcesses->cleanEndedProcesses();

			return static::CODE_ERROR;
		}

		// Enable Garbage Collector (PHP >= 5.3)
		if (function_exists('gc_enable')) {
			gc_enable();
		}
		if (function_exists('pcntl_signal')) {
			pcntl_signal(SIGTERM, [&$this, '_exit']);
			pcntl_signal(SIGINT, [&$this, '_abort']);
			pcntl_signal(SIGTSTP, [&$this, '_abort']);
			pcntl_signal(SIGQUIT, [&$this, '_abort']);
		}
		$this->_exit = false;

		$startTime = time();
		$groups = $this->_stringToArray($this->param('group'));
		$types = $this->_stringToArray($this->param('type'));

		while (!$this->_exit) {
			$this->_setPhpTimeout();

			try {
				$this->_updatePid($pid);
			} catch (RecordNotFoundException $exception) {
				// Manually killed
				$this->_exit = true;
				continue;
			} catch (ProcessEndingException $exception) {
				// Soft killed, e.g. during deploy update
				$this->_exit = true;
				continue;
			}

			if ($this->param('verbose')) {
				$this->_log('runworker', $pid, false);
			}
			$this->out('[' . date('Y-m-d H:i:s') . '] Looking for Job ...');

			$queuedJob = $this->QueuedJobs->requestJob($this->_getTaskConf(), $groups, $types);

			if ($queuedJob) {
				$this->runJob($queuedJob, $pid);
			} elseif (Configure::read('Queue.exitwhennothingtodo')) {
				$this->out('nothing to do, exiting.');
				$this->_exit = true;
			} else {
				$this->out('nothing to do, sleeping.');
				sleep(Config::sleeptime());
			}

			// check if we are over the maximum runtime and end processing if so.
			if (Configure::readOrFail('Queue.workermaxruntime') && (time() - $startTime) >= Configure::readOrFail('Queue.workermaxruntime')) {
				$this->_exit = true;
				$this->out('Reached runtime of ' . (time() - $startTime) . ' Seconds (Max ' . Configure::readOrFail('Queue.workermaxruntime') . '), terminating.');
			}
			if ($this->_exit || mt_rand(0, 100) > (100 - (int)Config::gcprob())) {
				$this->out('Performing Old job cleanup.');
				$this->QueuedJobs->cleanOldJobs();
				$this->QueueProcesses->cleanEndedProcesses();
			}
			$this->hr();
		}

		$this->_deletePid($pid);

		if ($this->param('verbose')) {
			$this->_log('endworker', $pid);
		}

		return static::CODE_SUCCESS;
	}

	/**
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 * @param string $pid
	 * @return void
	 */
	protected function runJob(QueuedJob $queuedJob, $pid) {
		$this->out('Running Job of type "' . $queuedJob->job_type . '"');
		$this->_log('job ' . $queuedJob->job_type . ', id ' . $queuedJob->id, $pid, false);
		$taskName = 'Queue' . $queuedJob->job_type;

		$return = $failureMessage = null;
		try {
			$this->_time = time();

			$data = unserialize($queuedJob->data);
			/** @var \Queue\Shell\Task\QueueTask $task */
			$task = $this->{$taskName};
			if (!$task instanceof QueueTaskInterface) {
				throw new RuntimeException('Task must implement ' . QueueTaskInterface::class);
			}

			$task->run((array)$data, $queuedJob->id);

		} catch (Throwable $e) {
			$return = false;

			$failureMessage = get_class($e) . ': ' . $e->getMessage();
			if (!($e instanceof QueueException)) {
				$failureMessage .= "\n" . $e->getTraceAsString();
			}

			$this->_logError($taskName . ' (job ' . $queuedJob->id . ')' . "\n" . $failureMessage, $pid);
		}

		if ($return === false) {
			$this->QueuedJobs->markJobFailed($queuedJob, $failureMessage);
			$failedStatus = $this->QueuedJobs->getFailedStatus($queuedJob, $this->_getTaskConf());
			$this->_log('job ' . $queuedJob->job_type . ', id ' . $queuedJob->id . ' failed and ' . $failedStatus, $pid);
			$this->out('Job did not finish, ' . $failedStatus . ' after try ' . $queuedJob->failed . '.');
			return;
		}

		$this->QueuedJobs->markJobDone($queuedJob);
		$this->out('Job Finished.');
	}

	/**
	 * Manually trigger a Finished job cleanup.
	 *
	 * @return void
	 */
	public function clean() {
		if (!Configure::read('Queue.cleanuptimeout')) {
			$this->abort('You disabled cleanuptimout in config. Aborting.');
		}

		$this->out('Deleting old jobs, that have finished before ' . date('Y-m-d H:i:s', time() - (int)Configure::read('Queue.cleanuptimeout')));
		$this->QueuedJobs->cleanOldJobs();
		$this->QueueProcesses->cleanEndedProcesses();
	}

	/**
	 * Gracefully end running workers when deploying.
	 *
	 * Use $in
	 * - all: to end all workers on all servers
	 * - server: to end the ones on this server
	 *
	 * @param string|null $in
	 * @return void
	 */
	public function end($in = null) {
		$processes = $this->QueuedJobs->getProcesses($in === 'server');
		if (!$processes) {
			$this->out('No processes found');

			return;
		}

		$this->out(count($processes) . ' processes:');
		foreach ($processes as $process => $timestamp) {
			$this->out(' - ' . $process . ' (last run @ ' . (new FrozenTime($timestamp)) . ')');
		}

		$options = array_keys($processes);
		$options[] = 'all';
		if ($in === null) {
			$in = $this->in('Process', $options, 'all');
		}

		if ($in === 'all' || $in === 'server') {
			foreach ($processes as $process => $timestamp) {
				$this->QueuedJobs->endProcess($process);
			}

			$this->out('All ' . count($processes) . ' processes ended.');

			return;
		}

		$this->QueuedJobs->endProcess((int)$in);
	}

	/**
	 * @return void
	 */
	public function kill() {
		$processes = $this->QueuedJobs->getProcesses();
		if (!$processes) {
			$this->out('No processes found');

			return;
		}

		$this->out(count($processes) . ' processes:');
		foreach ($processes as $process => $timestamp) {
			$this->out(' - ' . $process . ' (last run @ ' . (new FrozenTime($timestamp)) . ')');
		}

		if (Configure::read('Queue.multiserver')) {
			$this->abort('Cannot kill by PID in multiserver environment.');
		}

		$options = array_keys($processes);
		$options[] = 'all';
		$in = $this->in('Process', $options, 'all');

		if ($in === 'all') {
			foreach ($processes as $process => $timestamp) {
				$this->QueuedJobs->terminateProcess((int)$process);
			}

			return;
		}

		$this->QueuedJobs->terminateProcess((int)$in);
	}

	/**
	 * Manually reset (failed) jobs for re-run.
	 * Careful, this should not be done while a queue task is being run.
	 *
	 * @return void
	 */
	public function reset() {
		$this->out('Resetting...');

		$count = $this->QueuedJobs->reset();

		$this->success($count . ' jobs reset.');
	}

	/**
	 * Manually reset already successfully run jobs for re-run.
	 * Careful, this should not be done with non-idempotent jobs.
	 *
	 * This is mainly useful for debugging and local development,
	 * if you have to run sth again.
	 *
	 * @param string $type
	 * @param string|null $reference
	 * @return void
	 */
	public function rerun($type, $reference = null) {
		$this->out('Rerunning...');

		$count = $this->QueuedJobs->rerun($type, $reference);

		$this->success($count . ' jobs reset for re-run.');
	}

	/**
	 * Display current settings
	 *
	 * @return void
	 */
	public function settings() {
		$this->out('Current Settings:');
		$conf = (array)Configure::read('Queue');
		foreach ($conf as $key => $val) {
			if ($val === false) {
				$val = 'no';
			}
			if ($val === true) {
				$val = 'yes';
			}
			$this->out('* ' . $key . ': ' . print_r($val, true));
		}

		$this->out('');

		$status = $this->QueueProcesses->status();
		$this->out('Current running workers: ' . ($status ? $status['workers'] : '-'));
		$this->out('Last run: ' . ($status ? $status['time']->nice() : '-'));
	}

	/**
	 * Display some statistics about Finished Jobs.
	 *
	 * @return void
	 */
	public function stats() {
		$this->out('Total unfinished jobs: ' . $this->QueuedJobs->getLength());
		$this->out('Running workers (processes): ' . $this->QueueProcesses->findActive()->count());
		$this->out('Server name: ' . $this->QueueProcesses->buildServerString());
		$this->hr();

		$this->out('Jobs currently in the queue:');
		$types = $this->QueuedJobs->getTypes()->toArray();
		foreach ($types as $type) {
			$this->out(' - ' . str_pad($type, 20, ' ', STR_PAD_RIGHT) . ': ' . $this->QueuedJobs->getLength($type));
		}
		$this->hr();

		$this->out('Finished job statistics:');
		$data = $this->QueuedJobs->getStats();
		foreach ($data as $item) {
			$this->out(' - ' . $item['job_type'] . ': ');
			$this->out('   - Finished Jobs in Database: ' . $item['num']);
			$this->out('   - Average Job existence    : ' . str_pad(Number::precision($item['alltime'], 0), 8, ' ', STR_PAD_LEFT) . 's');
			$this->out('   - Average Execution delay  : ' . str_pad(Number::precision($item['fetchdelay'], 0), 8, ' ', STR_PAD_LEFT) . 's');
			$this->out('   - Average Execution time   : ' . str_pad(Number::precision($item['runtime'], 0), 8, ' ', STR_PAD_LEFT) . 's');
		}
	}

	/**
	 * Truncates the queue table
	 *
	 * @return void
	 */
	public function hardReset() {
		$this->QueuedJobs->truncate();
		$message = __d('queue', 'OK');

		$this->out($message);
	}

	/**
	 * Get option parser method to parse commandline options
	 *
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$subcommandParser = [
			'options' => [
				/*
				'dry-run'=> array(
					'short' => 'd',
					'help' => 'Dry run the update, no jobs will actually be added.',
					'boolean' => true
				),
				*/
			],
		];
		$subcommandParserFull = $subcommandParser;
		$subcommandParserFull['options']['group'] = [
			'short' => 'g',
			'help' => 'Group (comma separated list possible)',
			'default' => null,
		];
		$subcommandParserFull['options']['type'] = [
			'short' => 't',
			'help' => 'Type (comma separated list possible)',
			'default' => null,
		];

		$rerunParser = $subcommandParser;
		$rerunParser['arguments'] = [
			'type' => [
				'help' => 'Job type. You need to specify one.',
				'required' => true,
			],
			'reference' => [
				'help' => 'Reference.',
				'required' => false,
			],
		];

		return parent::getOptionParser()
			->setDescription($this->_getDescription())
			->addSubcommand('clean', [
				'help' => 'Remove old jobs (cleanup)',
				'parser' => $subcommandParser,
			])
			->addSubcommand('add', [
				'help' => 'Add Job',
				'parser' => $subcommandParser,
			])
			->addSubcommand('stats', [
				'help' => 'Stats',
				'parser' => $subcommandParserFull,
			])
			->addSubcommand('settings', [
				'help' => 'Settings',
				'parser' => $subcommandParserFull,
			])
			->addSubcommand('reset', [
				'help' => 'Manually reset (failed) jobs for re-run.',
				'parser' => $subcommandParserFull,
			])
			->addSubcommand('rerun', [
				'help' => 'Manually rerun (successfully) run job.',
				'parser' => $rerunParser,
			])
			->addSubcommand('hard_reset', [
				'help' => 'Hard reset queue (remove all jobs)',
				'parser' => $subcommandParserFull,
			])
			->addSubcommand('end', [
				'help' => 'Manually end a worker.',
				'parser' => $subcommandParserFull,
			])
			->addSubcommand('kill', [
				'help' => 'Manually kill a worker.',
				'parser' => $subcommandParserFull,
			])
			->addSubcommand('runworker', [
				'help' => 'Run Worker',
				'parser' => $subcommandParserFull,
			]);
	}

	/**
	 * Timestamped log.
	 *
	 * @param string $message Log type
	 * @param string|null $pid PID of the process
	 * @param bool $addDetails
	 * @return void
	 */
	protected function _log($message, $pid = null, $addDetails = true) {
		if (!Configure::read('Queue.log')) {
			return;
		}

		if ($addDetails) {
			$timeNeeded = $this->_timeNeeded();
			$memoryUsage = $this->_memoryUsage();
			$message .= ' [' . $timeNeeded . ', ' . $memoryUsage . ']';
		}

		if ($pid) {
			$message .= ' (pid ' . $pid . ')';
		}
		Log::write('info', $message, ['scope' => 'queue']);
	}

	/**
	 * @param string $message
	 * @param string|null $pid PID of the process
	 * @return void
	 */
	protected function _logError($message, $pid = null) {
		$timeNeeded = $this->_timeNeeded();
		$memoryUsage = $this->_memoryUsage();
		$message .= ' [' . $timeNeeded . ', ' . $memoryUsage . ']';

		if ($pid) {
			$message .= ' (pid ' . $pid . ')';
		}
		$serverString = $this->QueueProcesses->buildServerString();
		if ($serverString) {
			$message .= ' {' . $serverString . '}';
		}

		Log::write('error', $message);
	}

	/**
	 * Returns a List of available QueueTasks and their individual configuration.
	 *
	 * @return array
	 */
	protected function _getTaskConf() {
		if (!is_array($this->_taskConf)) {
			$this->_taskConf = Config::taskConfig($this->tasks);
		}

		return $this->_taskConf;
	}

	/**
	 * Signal handling to queue worker for clean shutdown
	 *
	 * @param int $signal
	 * @return void
	 */
	protected function _exit($signal) {
		$this->_exit = true;
	}

	/**
	 * Signal handling for Ctrl+C
	 *
	 * @param int $signal
	 * @return void
	 */
	protected function _abort($signal) {
		$this->_deletePid($this->_pid);
		exit(1);
	}

	/**
	 * @return void
	 */
	protected function _displayAvailableTasks() {
		$this->out('Available Tasks:');

		$tasks = $this->taskNames;
		sort($tasks);

		foreach ($tasks as $loadedTask) {
			$this->out("\t" . '* ' . $this->_taskName($loadedTask));
		}
	}

	/**
	 * @return string
	 */
	protected function _initPid() {
		$pid = $this->_retrievePid();
		$key = $this->QueuedJobs->key();
		$this->QueueProcesses->add($pid, $key);

		$this->_pid = $pid;

		return $pid;
	}

	/**
	 * @return string
	 */
	protected function _retrievePid() {
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
	protected function _updatePid($pid) {
		$this->QueueProcesses->update($pid);
	}

	/**
	 * @return string Memory usage in MB.
	 */
	protected function _memoryUsage() {
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
	protected function _deletePid($pid) {
		if (!$pid) {
			$pid = $this->_pid;
		}
		if (!$pid) {
			return;
		}

		$this->QueueProcesses->remove($pid);
	}

	/**
	 * @return string
	 */
	protected function _timeNeeded() {
		$diff = $this->_time() - $this->_time($this->_time);
		$seconds = max($diff, 1);

		return $seconds . 's';
	}

	/**
	 * @param int|null $providedTime
	 *
	 * @return int
	 */
	protected function _time($providedTime = null) {
		if ($providedTime) {
			return $providedTime;
		}

		return time();
	}

	/**
	 * @param string|null $param
	 * @return string[]
	 */
	protected function _stringToArray($param) {
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
	protected function _setPhpTimeout() {
		$timeLimit = (int)Configure::readOrFail('Queue.workermaxruntime') * 100;
		if (Configure::read('Queue.workertimeout') !== null) {
			$timeLimit = (int)Configure::read('Queue.workertimeout');
		}

		set_time_limit($timeLimit);
	}

}
