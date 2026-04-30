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

	public $uses = [
		'Queue.QueuedTask'
	];

/**
 * @var QueuedTask
 */
	public $QueuedTask;

/**
 * @var array
 */
	protected $_taskConf;

	protected $_exit;

	protected $_messagesProcessed = 0;

	protected $_workerLogFile = null;

/**
 * Overwrite shell initialize to dynamically load all Queue Related Tasks.
 *
 * @return void
 */
	public function initialize() {
		$paths = App::path('Console/Command/Task');

		foreach ($paths as $path) {
			$Folder = new Folder($path);
			$res = array_merge($this->tasks, $Folder->find('Queue.+\.php'));
			foreach ($res as &$r) {
				$r = basename($r, 'Task.php');
			}
			$this->tasks = $res;
		}
		$plugins = CakePlugin::loaded();
		foreach ($plugins as $plugin) {
			$pluginPaths = App::path('Console/Command/Task', $plugin);
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
 * @param string $task Taskname
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
			pcntl_signal(SIGTERM, [&$this, "_exit"]);
		}
		$this->_exit = false;

		$starttime = time();
		$baseRuntime = Configure::read('Queue.workermaxruntime');
		$jitter = (int) Configure::read('Queue.workermaxruntimejitter');
		$maxRuntime = $baseRuntime ? $baseRuntime + rand(0, $jitter) : 0;
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

			$data = $this->QueuedTask->requestJob($this->_getTaskConf(), $group);
			if ($this->QueuedTask->exit === true) {
				$this->_exit = true;
			} else {
        // check if we are over the memory limit and end processing if so
        $memoryLimit = Configure::read('Queue.workermaxmemory');
        $workerMaxMemoryTimeout = Configure::read('Queue.workermaxmemorytimeout');
        if ($memoryLimit) {
            $memoryUsage = $this->_humanReadableBytes(memory_get_usage(true));
            $this->out('Memory usage: ' . $memoryUsage);
          if ($memoryUsage >= $memoryLimit) {
            $this->out('Reached memory limit of ' . $memoryUsage . ' (Max ' . $memoryLimit . 'MB), skipping this job.');
            // Mark job as failed due to memory constraints
            $this->QueuedTask->markJobFailed($data['id'], 'Not enough memory to run this job. Worker memory usage hit ' . $memoryUsage . 'MB, over the ' . $memoryLimit . 'MB max limit.');
            if ($workerMaxMemoryTimeout) {
              $this->out('Exiting in ' . $workerMaxMemoryTimeout . ' seconds due to memory limit.');
              sleep($workerMaxMemoryTimeout);
              $this->_exit = true;
            }
            sleep(Configure::read('Queue.sleeptime'));
            $this->hr();
            continue;
          }
        }
				if ($data) {
					$this->out('Running Job of type "' . $data['jobtype'] . '"');
					$taskname = 'Queue' . $data['jobtype'];

					if ($this->{$taskname}->autoUnserialize) {
						$data['data'] = unserialize($data['data']);
					}
					//prevent tasks that don't catch their own errors from killing this worker
					try {
						$return = $this->{$taskname}->run($data['data'], $data['id']);
					} catch ( Exception $e)
					{
						//assume job failed
						$return = false;

						//log the exception
						$this->_logError( $taskname ."\n\n". $e->getMessage() ."\n\n". $e->getTraceAsString() );
					}

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
					$this->_messagesProcessed++;
				} elseif (Configure::read('Queue.exitwhennothingtodo')) {
					$this->out('nothing to do, exiting.');
					$this->_exit = true;
				} else {
					$this->out('nothing to do, sleeping.');
					sleep(Configure::read('Queue.sleeptime'));
				}

				// check if we are over the maximum runtime and end processing if so.
				if ($maxRuntime && (time() - $starttime) >= $maxRuntime) {
					$this->_exit = true;
					$this->out('Reached runtime of ' . (time() - $starttime) . ' Seconds (Max ' . $maxRuntime . '), terminating.');
				}
				// check if we have processed the maximum number of messages
				if (Configure::read('Queue.workermaxmessages') && $this->_messagesProcessed >= Configure::read('Queue.workermaxmessages')) {
					$this->_exit = true;
					$this->out('Processed ' . $this->_messagesProcessed . ' messages (Max ' . Configure::read('Queue.workermaxmessages') . '), exiting gracefully.');
				}
				if ($this->_exit || rand(0, 100) > (100 - Configure::read('Queue.gcprob'))) {
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
     * Run a QueueWorker loop.
     * Runs a Queue Worker process which will try to find unassigned jobs in the queue
     * which it may run and try to fetch and execute them.
     *
     * @return void
     */
    public function runworkersqs() {
        $this->_initWorkerLog();
        //queue url is passed in thru URL
        $queueUrl = $this->args[0];

        if(!$queueUrl) {
            $this->out('sqs queue url as first parameter is required');
            return;
        }

        // Check if running in ECS mode
        $enableEcs = !empty($this->params['enable-ecs']);
        if ($enableEcs) {
            $this->out('[ECS MODE] Only processing messages');
        } else {
            $this->out('[EC2 MODE] Only processing messages');
        }


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
            pcntl_signal(SIGTERM, [&$this, "_exit"]);
        }
        $this->_exit = false;

        $starttime = time();
        $baseRuntime = Configure::read('Queue.workermaxruntime');
        $jitter = (int) Configure::read('Queue.workermaxruntimejitter');
        $maxRuntime = $baseRuntime ? $baseRuntime + rand(0, $jitter) : 0;
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
            //$this->_log('runworker', isset($pid) ? $pid : null);
            $this->out('[' . date('Y-m-d H:i:s') . '] Looking for ' . ($enableEcs ? 'ECS' : 'EC2') . ' Job ...');

            $data = $this->QueuedTask->requestSqsJob($queueUrl);
            //$data = $this->QueuedTask->requestJob($this->_getTaskConf(), $group);
            if ($this->QueuedTask->exit === true) {
                $this->_exit = true;
            } else {
                // check if we are over the memory limit and end processing if so
                $memoryLimit = Configure::read('Queue.workermaxmemory');
                $workerMaxMemoryTimeout = Configure::read('Queue.workermaxmemorytimeout');
                if ($memoryLimit) {
                    $memoryUsage = $this->_humanReadableBytes(memory_get_usage(true));
                    $this->out('Memory usage: ' . $memoryUsage);
                  if ($memoryUsage >= $memoryLimit) {
                    $this->out('Reached memory limit of ' . $memoryUsage . ' (Max ' . $memoryLimit . 'MB), skipping this job.');
                    // Mark job as failed due to memory constraints
                    $this->QueuedTask->markJobFailed($data['id'], 'Not enough memory to run this job. Worker memory usage hit ' . $memoryUsage . 'MB, over the ' . $memoryLimit . 'MB max limit.');
                    if ($workerMaxMemoryTimeout) {
                      $this->out('Exiting in ' . $workerMaxMemoryTimeout . ' seconds due to memory limit.');
                      sleep($workerMaxMemoryTimeout);
                      $this->_exit = true;
                    }
                    sleep(Configure::read('Queue.sleeptime'));
                    $this->hr();
                    continue;
                  }
                }
                if ($data) {
                    $this->out('Running Job of type "' . $data['jobtype'] . '"');
                    // For ECS consumers, allow tasks suffixed with "-ECS" to map to their base task
                    // Remove the "-ECS" suffix to get the base task name
                    if ($enableEcs) {
                        $data['jobtype'] = preg_replace('/-ECS$/i', '', $data['jobtype']);
                    }
                    $taskname = 'Queue' . $data['jobtype'];

                    if ($this->{$taskname}->autoUnserialize) {
                        $data['data'] = unserialize($data['data']);
                    }
                    //prevent tasks that don't catch their own errors from killing this worker

                    try {
                        $return = $this->{$taskname}->run($data['data'], $data['id']);
                    } catch ( Exception $e)
                    {
                        //assume job failed
                        $return = false;

                        //log the exception
                        $this->_logError( $taskname ."\n\n". $e->getMessage() ."\n\n". $e->getTraceAsString() );
                    }

                    if ($return) {
                        $this->QueuedTask->markJobDoneSqs($data, $queueUrl);
                        $this->out('Job Finished.');
                    } else {
                        $failureMessage = null;
                        if (isset($this->{$taskname}->failureMessage) && !empty($this->{$taskname}->failureMessage)) {
                            $failureMessage = $this->{$taskname}->failureMessage;
                        }
                        $this->QueuedTask->markJobFailedSqs($data, $queueUrl, $failureMessage);
                        $this->out('Job did not finish, requeued.');
                    }
                    $this->_messagesProcessed++;
                } elseif (Configure::read('Queue.exitwhennothingtodo')) {
                    $this->out('nothing to do, exiting.');
                    $this->_exit = true;
                } else {
                    $this->out('nothing to do, sleeping.');
                    sleep(Configure::read('Queue.sleeptime'));
                }

                // check if we are over the maximum runtime and end processing if so.
                if ($maxRuntime && (time() - $starttime) >= $maxRuntime) {
                    $this->_exit = true;
                    $this->out('Reached runtime of ' . (time() - $starttime) . ' Seconds (Max ' . $maxRuntime . '), terminating.');
                }
                // check if we have processed the maximum number of messages
                if (Configure::read('Queue.workermaxmessages') && $this->_messagesProcessed >= Configure::read('Queue.workermaxmessages')) {
                    $this->_exit = true;
                    $this->out('Processed ' . $this->_messagesProcessed . ' messages (Max ' . Configure::read('Queue.workermaxmessages') . '), exiting gracefully.');
                }
                if ($this->_exit || rand(0, 100) > (100 - Configure::read('Queue.gcprob'))) {
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

/**
 * Get option parser method to parse commandline options
 *
 * @return OptionParser
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
					'help' => 'Log all ouput to file log.txt in TMP dir',
					'boolean' => true
				),
				*/
			]
		];
		$subcommandParserFull = $subcommandParser;
		$subcommandParserFull['options']['group'] = [
			'short' => 'g',
			'help' => 'Group',
			'default' => ''
		];

    $subcommandParserSqs = [
			'options' => [
				'enable-ecs' => [
					'help' => 'Enable ECS mode - only process messages',
					'boolean' => true,
					'default' => false
				]
			]
		];

		return parent::getOptionParser()
			->description(__d('cake_console', "Simple and minimalistic job queue (or deferred-task) system."))
			->addSubcommand('clean', [
				'help' => 'Remove old jobs (cleanup)',
				'parser' => $subcommandParser
			])
			->addSubcommand('add', [
				'help' => 'Add Job',
				'parser' => $subcommandParser
			])
			->addSubcommand('install', [
				'help' => 'Install info',
				'parser' => $subcommandParser
			])
			->addSubcommand('uninstall', [
				'help' => 'Uninstall info',
				'parser' => $subcommandParser
			])
			->addSubcommand('runworker', [
				'help' => 'Run Worker',
				'parser' => $subcommandParserFull
			])
      ->addSubcommand('runworkersqs', [
        'help' => 'Run Worker SQS',
        'parser' => $subcommandParserSqs
      ]);
	}

	protected function _initWorkerLog() {
		$dir = TMP . 'queue';
		if (!file_exists($dir)) {
			mkdir($dir, 0755, true);
		}
		$this->_workerLogFile = $dir . DS . 'worker.log';
	}

	public function out($message = '', $newlines = 1, $level = Shell::NORMAL) {
		if ($this->_workerLogFile) {
			if (@filesize($this->_workerLogFile) > 5 * 1024 * 1024) {
				@rename($this->_workerLogFile, $this->_workerLogFile . '.1');
			}
			$line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
			file_put_contents($this->_workerLogFile, $line, FILE_APPEND | LOCK_EX);
			return;
		}
		return parent::out($message, $newlines, $level);
	}

	public function hr($newlines = 0, $width = 63) {
		if ($this->_workerLogFile) {
			$line = str_repeat('-', $width) . "\n";
			file_put_contents($this->_workerLogFile, $line, FILE_APPEND | LOCK_EX);
			return;
		}
		return parent::hr($newlines, $width);
	}

/**
 * Timestamped log.
 *
 * @param string $type Log type
 * @param int $pid PID of the process
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
	 * Timestamped log.
	 *
	 * @param string $message
	 *
	 * @internal param string $type Log type
	 * @internal param int $pid PID of the process
	 */
	protected function _logError($message = '') {
		# log?
		if (Configure::read('Queue.log')) {
			CakeLog::write('queue-error', $message);
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
 *
 */
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

/**
 * Format bytes into human readable format
 *
 * @param int $bytes Number of bytes
 * @return string Human readable bytes format
 */
	protected function _humanReadableBytes($bytes) {
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);
		return round($bytes, 2) . ' ' . $units[$pow];
	}

}
