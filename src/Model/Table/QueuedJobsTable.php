<?php

namespace Queue\Model\Table;

use Cake\Core\Configure;
use Cake\I18n\Time;
use Cake\ORM\Table;
use Exception;
use Queue\Model\Entity\QueuedJob;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

// PHP 7.1+ has this defined
if (!defined('SIGTERM')) {
	define('SIGTERM', 15);
}

/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */
class QueuedJobsTable extends Table {

	/**
	 * @var array
	 */
	public $rateHistory = [];

	/**
	 * @var array
	 */
	public $findMethods = [
		'progress' => true,
	];

	/**
	 * @var string|null
	 */
	protected $_key = null;

	/**
	 * initialize Table
	 *
	 * @param array $config Configuration
	 * @return void
	 */
	public function initialize(array $config) {
		parent::initialize($config);

		$this->addBehavior('Timestamp');

		$this->initConfig();
	}

	/**
	 * @return void
	 */
	public function initConfig() {
		// Local config without extra config file
		$conf = (array)Configure::read('Queue');

		// Fallback to Plugin config which can be overwritten via local app config.
		Configure::load('Queue.app_queue');
		$defaultConf = (array)Configure::read('Queue');

		$conf = array_merge($defaultConf, $conf);

		Configure::write('Queue', $conf);
	}

	/**
	 * Add a new Job to the Queue.
	 *
	 *
	 * Config
	 * - priority: 1-10, defaults to 5
	 * - notBefore: Optional date which must not be preceded
	 * - group: Used to group similar QueuedJobs
	 * - reference: An optional reference string
	 *
	 * @param string $jobName Job name
	 * @param array|null $data Array of data
	 * @param array $config Config to save along with the job
	 * @return \Cake\ORM\Entity Saved job entity
	 * @throws \Exception
	 */
	public function createJob($jobName, array $data = null, array $config = []) {
		$queuedJob = [
			'job_type' => $jobName,
			'data' => is_array($data) ? json_encode($data) : null,
			'job_group' => !empty($config['group']) ? $config['group'] : null,
			'notbefore' => !empty($config['notBefore']) ? new Time($config['notBefore']) : null,
		] + $config;

		$queuedJob = $this->newEntity($queuedJob);
		if ($queuedJob->errors()) {
			throw new Exception('Invalid entity data');
		}
		return $this->save($queuedJob);
	}

	/**
	 * Returns the number of items in the queue.
	 * Either returns the number of ALL pending jobs, or the number of pending jobs of the passed type.
	 *
	 * @param string|null $type Job type to Count
	 * @return int
	 */
	public function getLength($type = null) {
		$findConf = [
			'conditions' => [
				'completed IS' => null,
			],
		];
		if ($type !== null) {
			$findConf['conditions']['job_type'] = $type;
		}
		$data = $this->find('all', $findConf);
		return $data->count();
	}

	/**
	 * Return a list of all job types in the Queue.
	 *
	 * @return array
	 */
	public function getTypes() {
		$findCond = [
			'fields' => [
				'job_type',
			],
			'group' => [
				'job_type',
			],
			'keyField' => 'job_type',
			'valueField' => 'job_type',
		];
		return $this->find('list', $findCond);
	}

	/**
	 * Return some statistics about finished jobs still in the Database.
	 * TO-DO: rewrite as virtual field
	 *
	 * @return array
	 */
	public function getStats() {
		$options = [
			'fields' => function ($query) {
				return [
					'job_type',
					'num' => $query->func()->count('*'),
					'alltime' => $query->func()->avg('UNIX_TIMESTAMP(completed) - UNIX_TIMESTAMP(created)'),
					'runtime' => $query->func()->avg('UNIX_TIMESTAMP(completed) - UNIX_TIMESTAMP(fetched)'),
					'fetchdelay' => $query->func()->avg('UNIX_TIMESTAMP(fetched) - IF(notbefore is NULL, UNIX_TIMESTAMP(created), UNIX_TIMESTAMP(notbefore))'),
				];
			},
			'conditions' => [
				'completed IS NOT' => null,
			],
			'group' => [
				'job_type',
			],
		];
		return $this->find('all', $options);
	}

	/**
	 * Look for a new job that can be processed with the current abilities and
	 * from the specified group (or any if null).
	 *
	 * @param array $capabilities Available QueueWorkerTasks.
	 * @param string|null $group Request a job from this group, (from any group if null)
	 * @return \Queue\Model\Entity\QueuedJob|null
	 */
	public function requestJob(array $capabilities, $group = null) {
		$now = new Time();
		$nowStr = $now->toDateTimeString();

		$query = $this->find();
		$options = [
			'conditions' => [
				'completed IS' => null,
				'OR' => [],
			],
			'fields' => [
				'age' => $query->newExpr()->add('IFNULL(TIMESTAMPDIFF(SECOND, "' . $nowStr . '", notbefore), 0)')
			],
			'order' => [
				'priority' => 'ASC',
				'age' => 'ASC',
				'id' => 'ASC',
			]
		];

		if ($group !== null) {
			$options['conditions']['job_group'] = $group;
		}

		// Generate the task specific conditions.
		foreach ($capabilities as $task) {
			list($plugin, $name) = pluginSplit($task['name']);
			$timeoutAt = $now->copy();
			$tmp = [
				'job_type' => $name,
				'AND' => [
					[
						'OR' => [
							'notbefore <' => $now,
							'notbefore IS' => null,
						],
					],
					[
						'OR' => [
							'fetched <' => $timeoutAt->subSeconds($task['timeout']),
							'fetched IS' => null,
						],
					],
				],
				'failed <' => ($task['retries'] + 1),
			];
			if (array_key_exists('rate', $task) && $tmp['job_type'] && array_key_exists($tmp['job_type'], $this->rateHistory)) {
				$tmp['UNIX_TIMESTAMP() >='] = $this->rateHistory[$tmp['job_type']] + $task['rate'];
			}
			$options['conditions']['OR'][] = $tmp;
		}

		$job = $this->connection()->transactional(function () use ($query, $options, $now) {
			$job = $query->find('all', $options)
				->autoFields(true)
				->first();

			if (!$job) {
				return null;
			}

			$key = $this->key();
			$job = $this->patchEntity($job, [
				'workerkey' => $key,
				'fetched' => $now
			]);

			return $this->save($job);
		});

		if (!$job) {
			return null;
		}

		$this->rateHistory[$job['job_type']] = $now->toUnixString();

		return $job;
	}

	/**
	 * @param int $id ID of job
	 * @param float $progress Value from 0 to 1
	 * @return bool Success
	 */
	public function updateProgress($id, $progress) {
		if (!$id) {
			return false;
		}
		return (bool)$this->updateAll(['progress' => round($progress, 2)], ['id' => $id]);
	}

	/**
	 * Mark a job as Completed, removing it from the queue.
	 *
	 * @param \Queue\Model\Entity\QueuedJob $job Job
	 * @return bool Success
	 */
	public function markJobDone(QueuedJob $job) {
		$fields = [
			'completed' => new Time(),
		];
		$job = $this->patchEntity($job, $fields);

		return (bool)$this->save($job);
	}

	/**
	 * Mark a job as Failed, incrementing the failed-counter and Requeueing it.
	 *
	 * @param \Queue\Model\Entity\QueuedJob $job Job
	 * @param string|null $failureMessage Optional message to append to the failure_message field.
	 * @return bool Success
	 */
	public function markJobFailed(QueuedJob $job, $failureMessage = null) {
		$fields = [
			'failed' => $job->failed + 1,
			'failure_message' => $failureMessage,
		];
		$job = $this->patchEntity($job, $fields);

		return (bool)$this->save($job);
	}

	/**
	 * Reset current jobs
	 *
	 * @return bool Success
	 */
	public function reset() {
		$fields = [
			'completed' => null,
			'fetched' => null,
			'progress' => 0,
			'failed' => 0,
			'workerkey' => null,
			'failure_message' => null,
		];
		$conditions = [
			'completed IS' => null,
		];
		return $this->updateAll($fields, $conditions);
	}

	/**
	 * Return some statistics about unfinished jobs still in the Database.
	 *
	 * @return \Cake\ORM\Query
	 */
	public function getPendingStats() {
		$findCond = [
			'fields' => [
				'job_type',
				'created',
				'status',
				'fetched',
				'progress',
				'reference',
				'failed',
				'failure_message',
			],
			'conditions' => [
				'completed IS' => null,
			],
		];
		return $this->find('all', $findCond);
	}

	/**
	 * Cleanup/Delete Completed Jobs.
	 *
	 * @return void
	 */
	public function cleanOldJobs() {
		$this->deleteAll([
			'completed <' => time() - Configure::read('Queue.cleanuptimeout'),
		]);
		$pidFilePath = Configure::read('Queue.pidfilepath');
		if (!$pidFilePath) {
			return;
		}
		// Remove all old pid files left over
		$timeout = time() - 2 * Configure::read('Queue.cleanuptimeout');
		$Iterator = new RegexIterator(
			new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pidFilePath)),
			'/^.+\_.+\.(pid)$/i',
			RegexIterator::MATCH
		);
		foreach ($Iterator as $file) {
			if ($file->isFile()) {
				$file = $file->getPathname();
				$lastModified = filemtime($file);
				if ($timeout > $lastModified) {
					unlink($file);
				}
			}
		}
	}

	/**
	 * @deprecated ?
	 * @return array
	 */
	public function lastRun() {
		$workerFileLog = LOGS . 'queue' . DS . 'runworker.txt';
		if (file_exists($workerFileLog)) {
			$worker = file_get_contents($workerFileLog);
		}
		return [
			'worker' => isset($worker) ? $worker : '',
			'queue' => $this->field('completed', ['completed IS NOT' => null], ['completed' => 'DESC']),
		];
	}

	/**
	 * Custom find method, as in `find('progress', ...)`.
	 *
	 * @param string $state Current state
	 * @param array $query Parameters
	 * @param array $results Results
	 * @return array         Query/Results based on state
	 */
	protected function _findProgress($state, $query = [], $results = []) {
		if ($state === 'before') {
			$query['fields'] = [
				'reference',
				'status',
				'progress',
				'failure_message',
			];
			if (isset($query['conditions']['exclude'])) {
				$exclude = $query['conditions']['exclude'];
				unset($query['conditions']['exclude']);
				$exclude = trim($exclude, ',');
				$exclude = explode(',', $exclude);
				$query['conditions'][] = [
					'NOT' => [
						'reference' => $exclude,
					],
				];
			}
			if (isset($query['conditions']['job_group'])) {
				$query['conditions'][]['job_group'] = $query['conditions']['job_group'];
				unset($query['conditions']['job_group']);
			}
			return $query;
		}
		// state === after
		foreach ($results as $k => $result) {
			$results[$k] = [
				'reference' => $result['reference'],
				'status' => $result['status'],
			];
			if (!empty($result['progress'])) {
				$results[$k]['progress'] = $result['progress'];
			}
			if (!empty($result['failure_message'])) {
				$results[$k]['failure_message'] = $result['failure_message'];
			}
		}
		return $results;
	}

	/**
	 * //FIXME
	 *
	 * @return void
	 */
	public function clearDoublettes() {
		$x = $this->_connection->query('SELECT max(id) as id FROM `' . $this->table() . '`
	WHERE completed is NULL
	GROUP BY data
	HAVING COUNT(id) > 1');

		$start = 0;
		$x = array_keys($x);
		$numX = count($x);
		while ($start <= $numX) {
			$this->deleteAll([
				'id' => array_slice($x, $start, 10),
			]);
			//debug(array_slice($x, $start, 10));
			$start = $start + 100;
		}
	}

	/**
	 * Generates a unique Identifier for the current worker thread.
	 *
	 * Useful to identify the currently running processes for this thread.
	 *
	 * @return string Identifier
	 */
	public function key() {
		if ($this->_key !== null) {
			return $this->_key;
		}
		$this->_key = sha1(microtime());
		return $this->_key;
	}

	/**
	 * Resets worker Identifier
	 *
	 * @return void
	 */
	public function clearKey() {
		$this->_key = null;
	}

	/**
	 * truncate()
	 *
	 * @return void
	 */
	public function truncate() {
		$sql = $this->schema()->truncateSql($this->_connection);
		foreach ($sql as $snippet) {
			$this->_connection->execute($snippet);
		}
	}

	/**
	 * @return array
	 */
	public function getProcesses() {
		if (!($pidFilePath = Configure::read('Queue.pidfilepath'))) {
			return [];
		}

		$processes = [];
		foreach (glob($pidFilePath . 'queue_*.pid') as $filename) {
			$time = filemtime($filename);
			preg_match('/\bqueue_(\d+)\.pid$/', $filename, $matches);
			$processes[$matches[1]] = $time;
		}

		return $processes;
	}

	/**
	 * @param int $pid
	 * @param int $sig Signal (defaults to graceful SIGTERM = 15)
	 * @return void
	 */
	public function terminateProcess($pid, $sig = SIGTERM) {
		$pidFilePath = Configure::read('Queue.pidfilepath');
		if (!$pidFilePath || !$pid) {
			return;
		}

		posix_kill($pid, $sig);
		sleep(1);
		$file = $pidFilePath . 'queue_' . $pid . '.pid';
		if (file_exists($file)) {
			unlink($file);
		}
	}

}
