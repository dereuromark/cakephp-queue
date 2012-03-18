<?php
App::uses('Folder', 'Utility');
App::uses('AppShell', 'Console/Command');
if (!defined('FORMAT_DB_DATE')) {
	define('FORMAT_DB_DATETIME', 'Y-m-d H:i:s');
}


/**
 * @author MGriesbach@gmail.com
 * @package QueuePlugin
 * @subpackage QueuePlugin.Shells
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */
class QueueShell extends AppShell {
	
	public $uses = array(
		'Queue.QueuedTask'
	);

	public $QueuedTask;
	
	protected $taskConf;

	/**
	 * Overwrite shell initialize to dynamically load all Queue Related Tasks.
	 */
	public function initialize() {
		$this->_loadModels();
		
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
		
		//die(returns($this->tasks));
		
		//Config can be overwritten via local app config.
		Configure::load('Queue.queue');
		
		$conf = (array)Configure::read('queue');
		//merge with default configuration vars.
		Configure::write('queue', array_merge(array(
			'sleeptime' => 10,
			'gcprop' => 10,
			'defaultworkertimeout' => 120,
			'defaultworkerretries' => 4,
			'workermaxruntime' => 0,
			'cleanuptimeout' => 2000,
			'exitwhennothingtodo' => false,
			'pidfilepath' => TMP . 'queue' . DS,
			'log' => false,
		), $conf));
		
	}
	
	public function main() {
		$this->help();
	}

	/**
	 * Output some basic usage Info.
	 */
	public function help() {
		$this->out('CakePHP Queue Plugin:');
		$this->hr();
		$this->out('Usage:');
		$this->out('	cake queue help');
		$this->out('		-> Display this Help message');
		$this->out('	cake queue add <taskname>');
		$this->out('		-> Try to call the cli add() function on a task');
		$this->out('		-> tasks may or may not provide this functionality.');
		$this->out('	cake queue runworker');
		$this->out('		-> run a queue worker, which will look for a pending task it can execute.');
		$this->out('		-> the worker will always try to find jobs matching its installed Tasks');
		$this->out('		-> see "Available Tasks" below.');
		$this->out('	cake queue stats');
		$this->out('		-> Display some general Statistics.');
		$this->out('	cake queue clean');
		$this->out('		-> Manually call cleanup function to delete task data of completed tasks.');
		$this->hr();
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
	 */
	public function add() {
		if (count($this->args) < 1) {
			$this->out('Please call like this:');
			$this->out('       cake queue add <taskname>');
			$this->out('Available Tasks:');
			foreach ($this->taskNames as $loadedTask) {
				$this->out(' * ' . $this->_taskName($loadedTask));
			}
			
		} else {
			$name = Inflector::camelize($this->args[0]);
			
			if (in_array($name.'', $this->taskNames)) {
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
	 * @param string $taskName
	 * @return string $cleanedTaskName
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
	 */
	public function runworker() {
		if ( $pidFilePath = Configure::read('queue.pidfilepath')) {
			if (!file_exists($pidFilePath)) {
				mkdir($pidFilePath, 0755, true);
			}
			if (function_exists('posix_getpid')) {
				$pid = posix_getpid();
			} else {
				$pid = $this->QueuedTask->key();
			}
			# global file
			$fp = fopen($pidFilePath.'queue.pid', "w");
	 		fwrite($fp, $pid);
	 		fclose($fp);
	 		# specific pid file
	 		if (function_exists('posix_getpid')) {
				$pid = posix_getpid();
			} else {
				$pid = $this->QueuedTask->key();
			}
			$pidFileName = 'queue_'.$pid.'.pid';
			$fp = fopen($pidFilePath . $pidFileName, "w");
	 		fwrite($fp, $pid);
	 		fclose($fp);	 		
		}
		// Enable Garbage Collector (PHP >= 5.3)
		if (function_exists('gc_enable')) {
		    gc_enable();
		}
		$exit = false;
		$starttime = time();
		$group = null;
		if (isset($this->params['group']) && !empty($this->params['group'])) {
			$group = $this->params['group'];
		}		
		while (!$exit) {
			// make sure accidental overriding isnt possible
			set_time_limit(0);
			if (!empty($pidFilePath)) {
				touch($pidFilePath.'queue.pid');
			}
			$this->_log('runworker');
			$this->out('Looking for Job....');
			
			$data = $this->QueuedTask->requestJob($this->_getTaskConf(), $group);
			if ($this->QueuedTask->exit === true) {
				$exit = true;
			} else {
				if ($data !== false) {
					$this->out('Running Job of type "' . $data['jobtype'] . '"');
					$taskname = 'Queue' . $data['jobtype'];
					$return = $this->{$taskname}->run(unserialize($data['data']));
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
				} elseif (Configure::read('queue.exitwhennothingtodo')) {
					$this->out('nothing to do, exiting.');
					$exit = true;
				} else {
					$this->out('nothing to do, sleeping.');
					sleep(Configure::read('queue.sleeptime'));
				}

				// check if we are over the maximum runtime and end processing if so.
				if (Configure::read('queue.workermaxruntime') != 0 && (time() - $starttime) >= Configure::read('queue.workermaxruntime')) {
					$exit = true;
					$this->out('Reached runtime of ' . (time() - $starttime) . ' Seconds (Max ' . Configure::read('queue.workermaxruntime') . '), terminating.');
				}
				if ($exit || rand(0, 100) > (100 - Configure::read('queue.gcprop'))) {
					$this->out('Performing Old job cleanup.');
					$this->QueuedTask->cleanOldJobs();
				}
				$this->hr();
			}
		}
		if(!empty($pidFilePath)) {
 			unlink($pidFilePath . 'queue_'.$pid.'.pid');
 		}
	}

	/**
	 * Manually trigger a Finished job cleanup.
	 * @return null
	 */
	public function clean() {
		$this->out('Deleting old jobs, that have finished before ' . date('Y-m-d H:i:s', time() - Configure::read('queue.cleanuptimeout')));
		$this->QueuedTask->cleanOldJobs();
	}

	/**
	 * Display Some statistics about Finished Jobs.
	 * @return null
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
	 * set up tables
	 */
	public function install() {
		
	}
	
	/**
	 * remove table and kill workers
	 */
	public function uninstall() {
		
	}
	
	
	/**
	 * timestamped log
	 * 2011-10-09 ms
	 */
	protected function _log($type) {
		# log?
		if (Configure::read('queue.log')) {
			$folder = LOGS.'queue';
			if (!file_exists($folder)) {
				mkdir($folder, 0755, true);
			}
			$file = $folder . DS . $type.'.txt';
			file_put_contents($file, date(FORMAT_DB_DATETIME));
		}
	}
	

	/**
	 * Returns a List of available QueueTasks and their individual configurations.
	 * @return array
	 */
	protected function _getTaskConf() {
		if (!is_array($this->taskConf)) {
			$this->taskConf = array();
			foreach ($this->tasks as $task) {
				list ($plugin, $taskName) = pluginSplit($task);
				$this->taskConf[$taskName]['name'] = $task;
				if (property_exists($this->{$taskName}, 'timeout')) {
					$this->taskConf[$taskName]['timeout'] = $this->{$taskName}->timeout;
				} else {
					$this->taskConf[$taskName]['timeout'] = Configure::read('queue.defaultworkertimeout');
				}
				if (property_exists($this->{$taskName}, 'retries')) {
					$this->taskConf[$taskName]['retries'] = $this->{$taskName}->retries;
				} else {
					$this->taskConf[$taskName]['retries'] = Configure::read('queue.defaultworkerretries');
				}
				if (property_exists($this->{$taskName}, 'rate')) {
					$this->taskConf[$taskName]['rate'] = $this->{$taskName}->rate;
				}
			}
		}
		return $this->taskConf;
	}
	
	public function __destruct() {
		if ($pidFilePath = Configure::read('queue.pidfilepath')) {
			if (function_exists('posix_getpid')) {
				$pid = posix_getpid();
			} else {
				$pid = $this->QueuedTask->key();
			}
			$file = $pidFilePath . 'queue_'.$pid.'.pid';
			if (file_exists($file)) {
				unlink($file);
			}
		}
	}
	
}
