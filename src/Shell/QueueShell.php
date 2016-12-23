<?php

namespace Queue\Shell;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Cake\I18n\Number;
use Cake\I18n\Time;
use Cake\Log\Log;
use Cake\Utility\Inflector;
use Exception;

declare(ticks = 1);

/**
 * Main shell to init and run queue workers.
 *
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 * @property \Queue\Model\Table\QueuedJobsTable $QueuedJobs
 */
class QueueShell extends Shell {

	/**
	 * @var string
	 */
	public $modelClass = 'Queue.QueuedJobs';

	/**
	 * @var array|null
	 */
	protected $_taskConf;

	/**
	 * @var bool
	 */
	protected $_exit = false;

	/**
	 * Overwrite shell initialize to dynamically load all Queue Related Tasks.
	 *
	 * @return void
	 */
	public function initialize() {
		$paths = App::path('Shell/Task');

		foreach ($paths as $path) {
			$Folder = new Folder($path);
			$res = array_merge($this->tasks, $Folder->find('Queue.+\.php'));
			foreach ($res as &$r) {
				$r = basename($r, 'Task.php');
			}
			$this->tasks = $res;
		}
		$plugins = Plugin::loaded();
		foreach ($plugins as $plugin) {
			$pluginPaths = App::path('Shell/Task', $plugin);
			foreach ($pluginPaths as $pluginPath) {
				$Folder = new Folder($pluginPath);
				$res = $Folder->find('Queue.+Task\.php');
				foreach ($res as &$r) {
					$r = $plugin . '.' . basename($r, 'Task.php');
				}
				$this->tasks = array_merge($this->tasks, $res);
			}
		}

		parent::initialize();

		$this->QueuedJobs->initConfig();
	}

	/**
	 * @return string
	 */
	public function _getDescription() {
		$tasks = [];
		foreach ($this->taskNames as $loadedTask) {
			$tasks[] = "\t" . '* ' . $this->_taskName($loadedTask);
		}
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
			$this->out('       bin/cake queue add <taskname>');
			$this->_displayAvailableTasks();

			return;
		}

		$name = Inflector::camelize($this->args[0]);

		if (in_array($name, $this->taskNames)) {
			$this->{$name}->add();
		} elseif (in_array('Queue' . $name . '', $this->taskNames)) {
			$this->{'Queue' . $name}->add();
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
	 * @return void
	 */
	public function runworker() {
		$pidFilePath = Configure::read('Queue.pidfilepath');
		if ($pidFilePath) {
			if (!file_exists($pidFilePath)) {
				mkdir($pidFilePath, 0755, true);
			}
			if (function_exists('posix_getpid')) {
				$pid = posix_getpid();
			} else {
				$pid = $this->QueuedJobs->key();
			}
			# global file
			$fp = fopen($pidFilePath . 'queue.pid', 'w');
			fwrite($fp, $pid);
			fclose($fp);
			# specific pid file
			if (function_exists('posix_getpid')) {
				$pid = posix_getpid();
			} else {
				$pid = $this->QueuedJobs->key();
			}
			$pidFileName = 'queue_' . $pid . '.pid';
			$fp = fopen($pidFilePath . $pidFileName, 'w');
			fwrite($fp, $pid);
			fclose($fp);
		}
		// Enable Garbage Collector (PHP >= 5.3)
		if (function_exists('gc_enable')) {
			gc_enable();
		}
		if (function_exists('pcntl_signal')) {
			pcntl_signal(SIGTERM, [&$this, '_exit']);
		}
		$this->_exit = false;

		$starttime = time();
		$group = null;
		if (!empty($this->params['group'])) {
			$group = $this->params['group'];
		}
		while (!$this->_exit) {
			// make sure accidental overriding isnt possible
			set_time_limit(0);
			if (!empty($pidFilePath)) {
				touch($pidFilePath . 'queue.pid');
			}
			if (!empty($pidFileName)) {
				touch($pidFilePath . $pidFileName);
			}
			$this->_log('runworker', isset($pid) ? $pid : null);
			$this->out('[' . date('Y-m-d H:i:s') . '] Looking for Job ...');

			$queuedTask = $this->QueuedJobs->requestJob($this->_getTaskConf(), $group);

			if ($queuedTask) {
				$this->out('Running Job of type "' . $queuedTask['job_type'] . '"');
				$taskname = 'Queue' . $queuedTask['job_type'];

				try {
					$data = json_decode($queuedTask['data'], true);
					/* @var \Queue\Shell\Task\QueueTask $task */
					$task = $this->{$taskname};
					$return = $task->run((array)$data, $queuedTask['id']);

					$failureMessage = null;
					if ($task->failureMessage) {
						$failureMessage = $task->failureMessage;
					}
				} catch (Exception $e) {
					$return = false;

					$failureMessage = get_class($e) . ': ' . $e->getMessage();
					//log the exception
					$this->_logError($taskname . "\n" . $failureMessage . "\n" . $e->getTraceAsString());
				}

				if ($return) {
					$this->QueuedJobs->markJobDone($queuedTask);
					$this->out('Job Finished.');
				} else {
					$this->QueuedJobs->markJobFailed($queuedTask, $failureMessage);
					$this->out('Job did not finish, requeued.');
				}
			} elseif (Configure::read('Queue.exitwhennothingtodo')) {
				$this->out('nothing to do, exiting.');
				$this->_exit = true;
			} else {
				$this->out('nothing to do, sleeping.');
				sleep(Configure::read('Queue.sleeptime'));
			}

			// check if we are over the maximum runtime and end processing if so.
			if (Configure::read('Queue.workermaxruntime') && (time() - $starttime) >= Configure::read('Queue.workermaxruntime')) {
				$this->_exit = true;
				$this->out('Reached runtime of ' . (time() - $starttime) . ' Seconds (Max ' . Configure::read('Queue.workermaxruntime') . '), terminating.');
			}
			if ($this->_exit || rand(0, 100) > (100 - Configure::read('Queue.gcprob'))) {
				$this->out('Performing Old job cleanup.');
				$this->QueuedJobs->cleanOldJobs();
			}
			$this->hr();
		}

		if (!empty($pid)) {
			if (file_exists($pidFilePath . 'queue_' . $pid . '.pid')) {
				unlink($pidFilePath . 'queue_' . $pid . '.pid');
			}
		}
	}

	/**
	 * @param string $message
	 * @return void
	 */
	protected function _logError($message) {
		if (Configure::read('Queue.log')) {
			Log::write('error', $message);
		}
	}

	/**
	 * Manually trigger a Finished job cleanup.
	 *
	 * @return void
	 */
	public function clean() {
		$this->out('Deleting old jobs, that have finished before ' . date('Y-m-d H:i:s', time() - Configure::read('Queue.cleanuptimeout')));
		$this->QueuedJobs->cleanOldJobs();
	}

	/**
	 * @return void
	 */
	public function kill() {
		$processes = $this->QueuedJobs->getProcesses();
		if (!$processes) {
			$this->out('No processed found');

			return;
		}

		$this->out(count($processes) . ' processes:');
		foreach ($processes as $process => $timestamp) {
			$this->out(' - ' . $process . ' (last run @ ' . (new Time($timestamp)) . ')');
		}

		$options = array_keys($processes);
		$options[] = 'all';
		$in = $this->in('Process', $options);

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
		$this->QueuedJobs->reset();
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
	}

	/**
	 * Display some statistics about Finished Jobs.
	 *
	 * @return void
	 */
	public function stats() {
		$this->out('Jobs currenty in the Queue:');

		$types = $this->QueuedJobs->getTypes()->toArray();
		foreach ($types as $type) {
			$this->out('      ' . str_pad($type, 20, ' ', STR_PAD_RIGHT) . ': ' . $this->QueuedJobs->getLength($type));
		}
		$this->hr();
		$this->out('Total unfinished Jobs      : ' . $this->QueuedJobs->getLength());
		$this->hr();
		$this->out('Finished Job Statistics:');
		$data = $this->QueuedJobs->getStats();
		foreach ($data as $item) {
			$this->out(' ' . $item['job_type'] . ': ');
			$this->out('   Finished Jobs in Database: ' . $item['num']);
			$this->out('   Average Job existence    : ' . str_pad(Number::precision($item['alltime']), 8, ' ', STR_PAD_LEFT) . 's');
			$this->out('   Average Execution delay  : ' . str_pad(Number::precision($item['fetchdelay']), 8, ' ', STR_PAD_LEFT) . 's');
			$this->out('   Average Execution time   : ' . str_pad(Number::precision($item['runtime']), 8, ' ', STR_PAD_LEFT) . 's');
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
	 * Set up tables
	 *
	 * @see README
	 * @return void
	 */
	public function install() {
		$this->out('Run `cake Migrations.migrate -p Queue`');
		$this->out('Set up cronjob, e.g. via `crontab -e -u www-data`');
	}

	/**
	 * Remove table and kill workers
	 *
	 * @return void
	 */
	public function uninstall() {
		$this->out('Remove all workers and cronjobs and then delete the Queue plugin tables.');
	}

	/**
	 * Get option parser method to parse commandline options
	 *
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser() {
		$subcommandParser = [
			'options' => [
				/*
				'dry-run'=> array(
					'short' => 'd',
					'help' => 'Dry run the update, no jobs will actually be added.',
					'boolean' => true
				),
				'log'=> array(
					'short' => 'l',
					'help' => 'Log all ouput to file log.txt in TMP dir'),
					'boolean' => true
				),
				*/
			],
		];
		$subcommandParserFull = $subcommandParser;
		$subcommandParserFull['options']['group'] = [
			'short' => 'g',
			'help' => 'Group',
			'default' => '',
		];

		return parent::getOptionParser()
			->description($this->_getDescription())
			->addSubcommand('clean', [
				'help' => 'Remove old jobs (cleanup)',
				'parser' => $subcommandParser,
			])
			->addSubcommand('add', [
				'help' => 'Add Job',
				'parser' => $subcommandParser,
			])
			->addSubcommand('install', [
				'help' => 'Install info',
				'parser' => $subcommandParser,
			])
			->addSubcommand('uninstall', [
				'help' => 'Uninstall info',
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
			->addSubcommand('hard_reset', [
				'help' => 'Hard reset queue (remove all jobs)',
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
	 * @param string $type Log type
	 * @param int|null $pid PID of the process
	 * @return void
	 */
	protected function _log($type, $pid = null) {
		# log?
		if (Configure::read('Queue.log')) {
			$folder = LOGS . 'queue';
			if (!file_exists($folder)) {
				mkdir($folder, 0755, true);
			}

			$message = $type . ' ' . $pid;
			// skip for now
			//Log::write('queue', $message);
		}
	}

	/**
	 * Timestamped notification.
	 *
	 * @return void
	 */
	protected function _notify() {
		# log?
		if (Configure::read('Queue.notify')) {
			$folder = TMP;
			$file = $folder . 'queue_notification' . '.txt';
			touch($file);
		}
	}

	/**
	 * Returns a List of available QueueTasks and their individual configurations.
	 *
	 * @return array
	 */
	protected function _getTaskConf() {
		if (!is_array($this->_taskConf)) {
			$this->_taskConf = [];
			foreach ($this->tasks as $task) {
				list($pluginName, $taskName) = pluginSplit($task);

				$this->_taskConf[$taskName]['name'] = substr($taskName, 5);
				$this->_taskConf[$taskName]['plugin'] = $pluginName;
				if (property_exists($this->{$taskName}, 'timeout')) {
					$this->_taskConf[$taskName]['timeout'] = $this->{$taskName}->timeout;
				} else {
					$this->_taskConf[$taskName]['timeout'] = Configure::read('Queue.defaultworkertimeout');
				}
				if (property_exists($this->{$taskName}, 'retries')) {
					$this->_taskConf[$taskName]['retries'] = $this->{$taskName}->retries;
				} else {
					$this->_taskConf[$taskName]['retries'] = Configure::read('Queue.defaultworkerretries');
				}
				if (property_exists($this->{$taskName}, 'rate')) {
					$this->_taskConf[$taskName]['rate'] = $this->{$taskName}->rate;
				}
			}
		}
		return $this->_taskConf;
	}

	/**
	 * Signal handling to queue worker for clean shutdown
	 *
	 * @param int $signal not used
	 * @return void
	 */
	protected function _exit($signal) {
		$this->_exit = true;
	}

	/**
	 * Destructor, removes pid-file
	 */
	public function __destruct() {
		$pidFilePath = Configure::read('Queue.pidfilepath');
		if (!$pidFilePath) {
			return;
		}

		if (function_exists('posix_getpid')) {
			$pid = posix_getpid();
		} else {
			$pid = $this->QueuedJobs->key();
		}
		$file = $pidFilePath . 'queue_' . $pid . '.pid';
		if (file_exists($file)) {
			unlink($file);
		}
	}

	/**
	 * @return void
	 */
	protected function _displayAvailableTasks() {
		$this->out('Available Tasks:');
		foreach ($this->taskNames as $loadedTask) {
			$this->out("\t" . '* ' . $this->_taskName($loadedTask));
		}
	}

}
