<?php
declare(ticks = 1);

if (!defined('FORMAT_DB_DATETIME')) {
	define('FORMAT_DB_DATETIME', 'Y-m-d H:i:s');
}

App::uses('Folder', 'Utility');
App::uses('AppShell', 'Console/Command');

/**
 * Main shell to init and run queue workers.
 *
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */
class QueueShell extends AppShell {

	public $uses = array(
		'Queue.QueuedTask'
	);

	/**
	 * @var QueuedTask
	 */
	public $QueuedTask;

	/**
	 * @var array
	 */
	protected $_taskConf;

	protected $_exit;

	/**
	 * Overwrite shell initialize to dynamically load all Queue Related Tasks.
	 *
	 * @return void
	 */
	public function initialize() {
		$paths = App::path('Console/Command/Task');

		foreach ($paths as $path) {
			$Folder = new Folder($path);
			$res = array_merge($this->tasks, $Folder->find('Queue.*\.php'));
			foreach ($res as &$r) {
				$r = basename($r, 'Task.php');
			}
			$this->tasks = $res;
		}
		$plugins = App::objects('plugin');
		foreach ($plugins as $plugin) {
			$pluginPaths = App::path('Console/Command/Task', $plugin);
			foreach ($pluginPaths as $pluginPath) {
				$Folder = new Folder($pluginPath);
				$res = $Folder->find('Queue.*Task\.php');
				foreach ($res as &$r) {
					$r = $plugin . '.' . basename($r, 'Task.php');
				}
				$this->tasks = array_merge($this->tasks, $res);
			}
		}

		parent::initialize();

		$this->QueuedTask->initConfig();
	}

	/**
	 * Output some basic usage Info.
	 *
	 * @return void
	 */
	public function main() {
		$this->out('CakePHP Queue Plugin:');
		$this->hr();
		$this->out('Usage:');
		$this->out('	cake Queue.Queue help');
		$this->out('		-> Display this Help message');
		$this->out('	cake Queue.Queue add <taskname>');
		$this->out('		-> Try to call the cli add() function on a task');
		$this->out('		-> tasks may or may not provide this functionality.');
		$this->out('	cake Queue.Queue runworker');
		$this->out('		-> run a queue worker, which will look for a pending task it can execute.');
		$this->out('		-> the worker will always try to find jobs matching its installed Tasks');
		$this->out('		-> see "Available Tasks" below.');
		$this->out('	cake Queue.Queue stats');
		$this->out('		-> Display some general Statistics.');
		$this->out('	cake Queue.Queue clean');
		$this->out('		-> Manually call cleanup function to delete task data of completed tasks.');
		$this->out('Notes:');
		$this->out('	<taskname> may either be the complete classname (eg. QueueExample)');
		$this->out('	or the shorthand without the leading "Queue" (eg. Example)');
		$this->out('Available Tasks:');
		foreach ($this->taskNames as $loadedTask) {
			$this->out("\t" . '* ' . $this->_taskName($loadedTask));
		}
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
			$this->out('       cake Queue.Queue add <taskname>');
			$this->out('Available Tasks:');
			foreach ($this->taskNames as $loadedTask) {
				$this->out(' * ' . $this->_taskName($loadedTask));
			}

		} else {
			$name = Inflector::camelize($this->args[0]);

			if (in_array($name, $this->taskNames)) {
				$this->{$name}->add();
			} elseif (in_array('Queue' . $name . '', $this->taskNames)) {
				$this->{'Queue' . $name}->add();
			} else {
				$this->out('Error: Task not Found: ' . $name);
				$this->out('Available Tasks:');
				foreach ($this->taskNames as $loadedTask) {
					$this->out(' * ' . $this->_taskName($loadedTask));
				}
			}
		}
	}

	/**
	 * Output the task without Queue or Task
	 * example: QueueImageTask becomes Image on display
	 *
	 * @param string $taskName
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
		if ($pidFilePath = Configure::read('Queue.pidfilepath')) {
			if (!file_exists($pidFilePath)) {
				mkdir($pidFilePath, 0755, true);
			}
			if (function_exists('posix_getpid')) {
				$pid = posix_getpid();
			} else {
				$pid = $this->QueuedTask->key();
			}
			# global file
			$fp = fopen($pidFilePath . 'queue.pid', "w");
			fwrite($fp, $pid);
			fclose($fp);
			# specific pid file
			if (function_exists('posix_getpid')) {
				$pid = posix_getpid();
			} else {
				$pid = $this->QueuedTask->key();
			}
			$pidFileName = 'queue_' . $pid . '.pid';
			$fp = fopen($pidFilePath . $pidFileName, "w");
			fwrite($fp, $pid);
			fclose($fp);
		}
		// Enable Garbage Collector (PHP >= 5.3)
		if (function_exists('gc_enable')) {
			gc_enable();
		}
		if (function_exists('pcntl_signal')) {
			pcntl_signal(SIGTERM, array(&$this, "_exit"));
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
			$this->out('Looking for Job....');
			$data = $this->QueuedTask->requestJob($this->_getTaskConf(), $group);
			if ($this->QueuedTask->exit === true) {
				$this->_exit = true;
			} else {
				if ($data) {
					$this->out('Running Job of type "' . $data['jobtype'] . '"');
					$taskname = 'Queue' . $data['jobtype'];

					if ($this->{$taskname}->autoUnserialize) {
						$data['data'] = unserialize($data['data']);
					}
					$return = $this->{$taskname}->run($data['data'], $data['id']);
					if ($return) {
						$this->QueuedTask->markJobDone($data['id']);
						$this->out('Job Finished.');
					} else {
						$failureMessage = null;
						if (isset($this->{$taskname}->failureMessage) && !empty($this->{$taskname}->failureMessage)) {
							$failureMessage = $this->{$taskname}->failureMessage;
						}
						$this->QueuedTask->markJobFailed($data['id'], $failureMessage);
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
				if ($this->_exit || rand(0, 100) > (100 - Configure::read('Queue.gcprop'))) {
					$this->out('Performing Old job cleanup.');
					$this->QueuedTask->cleanOldJobs();
				}
				$this->hr();
			}
		}
		if (!empty($pidFilePath)) {
			unlink($pidFilePath . 'queue_' . $pid . '.pid');
		}
	}

	/**
	 * Manually trigger a Finished job cleanup.
	 *
	 * @return void
	 */
	public function clean() {
		$this->out('Deleting old jobs, that have finished before ' . date('Y-m-d H:i:s', time() - Configure::read('Queue.cleanuptimeout')));
		$this->QueuedTask->cleanOldJobs();
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

		$types = $this->QueuedTask->getTypes();

		foreach ($types as $type) {
			$this->out("      " . str_pad($type, 20, ' ', STR_PAD_RIGHT) . ": " . $this->QueuedTask->getLength($type));
		}
		$this->hr();
		$this->out('Total unfinished Jobs      : ' . $this->QueuedTask->getLength());
		$this->hr();
		$this->out('Finished Job Statistics:');
		$data = $this->QueuedTask->getStats();
		foreach ($data as $item) {
			$this->out(" " . $item['QueuedTask']['jobtype'] . ": ");
			$this->out("   Finished Jobs in Database: " . $item[0]['num']);
			$this->out("   Average Job existence    : " . $item[0]['alltime'] . 's');
			$this->out("   Average Execution delay  : " . $item[0]['fetchdelay'] . 's');
			$this->out("   Average Execution time   : " . $item[0]['runtime'] . 's');
		}
	}

	/**
	 * Set up tables
	 *
	 * @see readme
	 * @return void
	 */
	public function install() {
		$this->out('Run `cake Schema create -p Queue`');
	}

	/**
	 * Remove table and kill workers
	 *
	 * @return void
	 */
	public function uninstall() {
		$this->out('Remove all workers and then delete the two tables.');
	}

	public function getOptionParser() {
		$subcommandParser = array(
			'options' => array(
				/*
				'dry-run'=> array(
					'short' => 'd',
					'help' => __d('cake_console', 'Dry run the update, no jobs will actually be added.'),
					'boolean' => true
				),
				'log'=> array(
					'short' => 'l',
					'help' => __d('cake_console', 'Log all ouput to file log.txt in TMP dir'),
					'boolean' => true
				),
				*/
			)
		);
		$subcommandParserFull = $subcommandParser;
		$subcommandParserFull['options']['group'] = array(
			'short' => 'g',
			'help' => __d('cake_console', 'Group'),
			'default' => ''
		);

		return parent::getOptionParser()
			->description(__d('cake_console', "..."))
			->addSubcommand('clean', array(
				'help' => __d('cake_console', 'Remove old jobs (cleanup)'),
				'parser' => $subcommandParser
			))
			->addSubcommand('add', array(
				'help' => __d('cake_console', 'Add Job'),
				'parser' => $subcommandParser
			))
			->addSubcommand('install', array(
				'help' => __d('cake_console', 'Install info'),
				'parser' => $subcommandParser
			))
			->addSubcommand('uninstall', array(
				'help' => __d('cake_console', 'Uninstall info'),
				'parser' => $subcommandParser
			))
			->addSubcommand('runworker', array(
				'help' => __d('cake_console', 'Run Worker'),
				'parser' => $subcommandParserFull
			));
	}

	/**
	 * Timestamped log.
	 *
	 * @return void
	 */
	protected function _log($type, $pid = null) {
		# log?
		if (Configure::read('Queue.log')) {
			$folder = LOGS . 'queue';
			if (!file_exists($folder)) {
				mkdir($folder, 0755, true);
			}
			//$file = $folder . DS . $type . '.txt';
			//file_put_contents($file, date(FORMAT_DB_DATETIME));
			$message = $type . ' ' . $pid;
			CakeLog::write('queue', $message);
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
			//file_put_contents($file, date(FORMAT_DB_DATETIME));
		}
	}

	/**
	 * Returns a List of available QueueTasks and their individual configurations.
	 *
	 * @return array
	 */
	protected function _getTaskConf() {
		if (!is_array($this->_taskConf)) {
			$this->_taskConf = array();
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
	 * @param integer $signal
	 * @return void
	 */
	protected function _exit($signal) {
		$this->_exit = true;
	}

	public function __destruct() {
		if ($pidFilePath = Configure::read('Queue.pidfilepath')) {
			if (function_exists('posix_getpid')) {
				$pid = posix_getpid();
			} else {
				$pid = $this->QueuedTask->key();
			}
			$file = $pidFilePath . 'queue_' . $pid . '.pid';
			if (file_exists($file)) {
				unlink($file);
			}
		}
	}

}
