<?php

namespace Queue\Shell;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Cake\I18n\Number;
use Cake\Log\Engine\FileLog;
use Cake\Log\Log;
use Cake\Utility\Inflector;

declare(ticks = 1);

/**
 * Main shell to init and run queue workers.
 *
 * @author  MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link    http://github.com/MSeven/cakephp_queue
 */
class QueueShell extends Shell {

	/**
	 * @var string
	 */
	public $modelClass = 'Queue.QueuedTasks';

	/**
	 * @var array
	 */
	protected $_taskConf;

	/**
	 * @var bool
	 */
	protected $_exit;

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

		//configure the logger if it is set to true
		if (Configure::read('Queue.log')) {
			Log::drop('debug');
			Log::drop('error');
			Log::config(
				'queue',
				function () {
					return new FileLog(
						[
						'path' => LOGS,
						'file' => 'queue'
						]
					);
				}
			);
		}

		parent::initialize();

		$this->QueuedTasks->initConfig();
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
	 * @param  string $task Taskname
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
				$pid = $this->QueuedTasks->key();
			}
			// global file
			$fp = fopen($pidFilePath . 'queue.pid', 'w');
			fwrite($fp, $pid);
			fclose($fp);
			// specific pid file
			if (function_exists('posix_getpid')) {
				$pid = posix_getpid();
			} else {
				$pid = $this->QueuedTasks->key();
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

			$data = $this->QueuedTasks->requestJob($this->_getTaskConf(), $group);
			if ($this->QueuedTasks->exit === true) {
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
						$this->QueuedTasks->markJobDone($data['id']);
						$this->out('Job Finished.');
					} else {
						$failureMessage = null;
						if (!empty($this->{$taskname}->failureMessage)) {
							$failureMessage = $this->{$taskname}->failureMessage;
						}
						$this->QueuedTasks->markJobFailed($data['id'], $failureMessage);
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
					$this->QueuedTasks->cleanOldJobs();
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
		$this->QueuedTasks->cleanOldJobs();
	}

	/**
	 * Manually reset (failed) jobs for re-run.
	 * Careful, this should not be done while a queue task is being run.
	 *
	 * @return void
	 */
	public function reset() {
		$this->out('Resetting...');
		$this->QueuedTasks->reset();
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

		$types = $this->QueuedTasks->getTypes()->toArray();
		foreach ($types as $type) {
			$this->out('      ' . str_pad($type, 20, ' ', STR_PAD_RIGHT) . ': ' . $this->QueuedTasks->getLength($type));
		}
		$this->hr();
		$this->out('Total unfinished Jobs      : ' . $this->QueuedTasks->getLength());
		$this->hr();
		$this->out('Finished Job Statistics:');
		$data = $this->QueuedTasks->getStats();
		foreach ($data as $item) {
			$this->out(' ' . $item['jobtype'] . ': ');
			$this->out('   Finished Jobs in Database: ' . $item['num']);
			$this->out('   Average Job existence    : ' . str_pad(Number::precision($item['alltime']), 8, ' ', STR_PAD_LEFT) . 's');
			$this->out('   Average Execution delay  : ' . str_pad(Number::precision($item['fetchdelay']), 8, ' ', STR_PAD_LEFT) . 's');
			$this->out('   Average Execution time   : ' . str_pad(Number::precision($item['runtime']), 8, ' ', STR_PAD_LEFT) . 's');
		}
	}

	/**
	 * Set up tables
	 *
	 * @see    readme
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
		 ->description('Simple and minimalistic job queue (or deferred-task) system.')
		 ->addSubcommand(
			 'clean',
			 [
			 'help' => 'Remove old jobs (cleanup)',
			 'parser' => $subcommandParser,
			 ]
		 )
		 ->addSubcommand(
			 'add',
			 [
			 'help' => 'Add Job',
			 'parser' => $subcommandParser,
			 ]
		 )
		 ->addSubcommand(
			 'install',
			 [
			 'help' => 'Install info',
			 'parser' => $subcommandParser,
			 ]
		 )
		 ->addSubcommand(
			 'uninstall',
			 [
			 'help' => 'Uninstall info',
			 'parser' => $subcommandParser,
			 ]
		 )
		 ->addSubcommand(
			 'stats',
			 [
			 'help' => 'Stats',
			 'parser' => $subcommandParserFull,
			 ]
		 )
		 ->addSubcommand(
			 'reset',
			 [
			 'help' => 'Stats',
			 'parser' => $subcommandParserFull,
			 ]
		 )
		 ->addSubcommand(
			 'runworker',
			 [
			 'help' => 'Run Worker',
			 'parser' => $subcommandParserFull,
			 ]
		 );
	}

	/**
	 * Timestamped log.
	 *
	 * @param  string   $type Log type
	 * @param  int|null $pid  PID of the process
	 * @return void
	 */
	protected function _log($type, $pid = null) {
		// log?
		if (Configure::read('Queue.log')) {
			$message = $type . ' ' . $pid;
			Log::write('debug', $message);
		}
	}

	/**
	 * Timestamped notification.
	 *
	 * @return void
	 */
	protected function _notify() {
		// log?
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
	 * @param  int $signal not used
	 * @return void
	 */
	protected function _exit($signal) {
		$this->_exit = true;
	}

	/**
	 * Destructor, removes pid-file
	 */
	public function __destruct() {
		if ($pidFilePath = Configure::read('Queue.pidfilepath')) {
			if (function_exists('posix_getpid')) {
				$pid = posix_getpid();
			} else {
				$pid = $this->QueuedTasks->key();
			}
			$file = $pidFilePath . 'queue_' . $pid . '.pid';
			if (file_exists($file)) {
				unlink($file);
			}
		}
	}

}
