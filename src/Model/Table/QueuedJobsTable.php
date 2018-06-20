<?php

namespace Queue\Model\Table;

use Cake\Core\Configure;
use Cake\I18n\Time;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Exception;
use InvalidArgumentException;
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
 * @method \Queue\Model\Entity\QueuedJob get($primaryKey, $options = [])
 * @method \Queue\Model\Entity\QueuedJob newEntity($data = null, array $options = [])
 * @method \Queue\Model\Entity\QueuedJob[] newEntities(array $data, array $options = [])
 * @method \Queue\Model\Entity\QueuedJob|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Queue\Model\Entity\QueuedJob patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Queue\Model\Entity\QueuedJob[] patchEntities($entities, array $data, array $options = [])
 * @method \Queue\Model\Entity\QueuedJob findOrCreate($search, callable $callback = null, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @method \Queue\Model\Entity\QueuedJob|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 */
class QueuedJobsTable extends Table {

	const DRIVER_MYSQL = 'Mysql';
	const DRIVER_POSTGRES = 'Postgres';
	const DRIVER_SQLSERVER = 'Sqlserver';

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
	 * set connection name
	 *
	 * @return string
	 */
	public static function defaultConnectionName() {
		$connection = Configure::read('Queue.connection');
		if (!empty($connection)) {
			return $connection;
		};

		return parent::defaultConnectionName();
	}

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
			'data' => is_array($data) ? serialize($data) : null,
			'job_group' => !empty($config['group']) ? $config['group'] : null,
			'notbefore' => !empty($config['notBefore']) ? new Time($config['notBefore']) : null,
		] + $config;

		$queuedJob = $this->newEntity($queuedJob);
		if ($queuedJob->getErrors()) {
			throw new Exception('Invalid entity data');
		}
		return $this->save($queuedJob);
	}

	/**
	 * @param string $reference
	 *
	 * @return bool
	 */
	public function isQueued($reference) {
		if (!$reference) {
			throw new InvalidArgumentException('A reference is needed');
		}

		return (bool)$this->find()->where(['reference' => $reference, 'completed IS' => null])->select(['id'])->first();
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
	 * @return \Cake\ORM\Query
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
	 * @return \Cake\ORM\Query
	 */
	public function getStats() {
		$driverName = $this->_getDriverName();
		$options = [
			'fields' => function ($query) use ($driverName) {
				$alltime = $query->func()->avg('UNIX_TIMESTAMP(completed) - UNIX_TIMESTAMP(created)');
				$runtime = $query->func()->avg('UNIX_TIMESTAMP(completed) - UNIX_TIMESTAMP(fetched)');
				$fetchdelay = $query->func()->avg('UNIX_TIMESTAMP(fetched) - IF(notbefore is NULL, UNIX_TIMESTAMP(created), UNIX_TIMESTAMP(notbefore))');
				switch ($driverName) {
					case static::DRIVER_SQLSERVER:
						$alltime = $query->func()->avg("DATEDIFF(s, '1970-01-01 00:00:00', completed) - DATEDIFF(s, '1970-01-01 00:00:00', created)");
						$runtime = $query->func()->avg("DATEDIFF(s, '1970-01-01 00:00:00', completed) - DATEDIFF(s, '1970-01-01 00:00:00', fetched)");
						$fetchdelay = $query->func()->avg("DATEDIFF(s, '1970-01-01 00:00:00', fetched) - (CASE WHEN notbefore IS NULL THEN DATEDIFF(s, '1970-01-01 00:00:00', created) ELSE DATEDIFF(s, '1970-01-01 00:00:00', notbefore) END)");
						break;
				}
					/**
						 * @var \Cake\ORM\Query
						 */
				return [
					'job_type',
					'num' => $query->func()->count('*'),
					'alltime' => $alltime,
					'runtime' => $runtime,
					'fetchdelay' => $fetchdelay,
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
		$driverName = $this->_getDriverName();

		$query = $this->find();
		$age = $query->newExpr()->add('IFNULL(TIMESTAMPDIFF(SECOND, "' . $nowStr . '", notbefore), 0)');
		switch ($driverName) {
			case static::DRIVER_SQLSERVER:
				$age = $query->newExpr()->add('ISNULL(DATEDIFF(SECOND, GETDATE(), notbefore), 0)');
				break;
		}
		$options = [
			'conditions' => [
				'completed IS' => null,
				'OR' => [],
			],
			'fields' => [
				'age' => $age
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
				switch ($driverName) {
					case static::DRIVER_POSTGRES:
					case static::DRIVER_MYSQL:
						$tmp['UNIX_TIMESTAMP() >='] = $this->rateHistory[$tmp['job_type']] + $task['rate'];
						break;
					case static::DRIVER_SQLSERVER:
						$tmp["DATEDIFF(s, '1970-01-01 00:00:00', GETDATE()) >="] = $this->rateHistory[$tmp['job_type']] + $task['rate'];
						break;
				}
			}
			$options['conditions']['OR'][] = $tmp;
		}

		$job = $this->getConnection()->transactional(function () use ($query, $options, $now) {
			$job = $query->find('all', $options)
				->enableAutoFields(true)
				->epilog('FOR UPDATE')
				->first();

			if (!$job) {
				return null;
			}

			$key = $this->key();
			$job = $this->patchEntity($job, [
				'workerkey' => $key,
				'fetched' => $now
			]);

			return $this->saveOrFail($job);
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
	 * @param int|null $id
	 *
	 * @return bool Success
	 */
	public function reset($id = null) {
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
		if ($id) {
			$conditions['id'] = $id;
		}

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
				'id',
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
		if (!Configure::read('Queue.cleanuptimeout')) {
			return;
		}

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
	 * @param \Queue\Model\Entity\QueuedJob $queuedTask
	 * @param array $taskConfiguration
	 * @return string
	 */
	public function getFailedStatus($queuedTask, array $taskConfiguration) {
		$failureMessageRequeued = 'requeued';

		$queuedTaskName = 'Queue' . $queuedTask->job_type;
		if (empty($taskConfiguration[$queuedTaskName])) {
			return $failureMessageRequeued;
		}
		$retries = $taskConfiguration[$queuedTaskName]['retries'];
		if ($queuedTask->failed <= $retries) {
			return $failureMessageRequeued;
		}

		return 'aborted';
	}

	/**
	 * Custom find method, as in `find('progress', ...)`.
	 *
	 * @param string $state Current state
	 * @param array $query Parameters
	 * @param array $results Results
	 * @return array Query/Results based on state
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
		$pidFilePath = Configure::read('Queue.pidfilepath');
		if (!$pidFilePath) {
			/** @var \Queue\Model\Table\QueueProcessesTable $QueueProcesses */
			$QueueProcesses = TableRegistry::get('Queue.QueueProcesses');
			$processes = $QueueProcesses->findActive()->hydrate(false)->find('list', ['keyField' => 'pid', 'valueField' => 'modified'])->all()->toArray();

			return $processes;
		}

		$processes = [];
		foreach (glob($pidFilePath . 'queue_*.pid') as $filename) {
			$time = filemtime($filename);
			preg_match('/\bqueue_([0-9a-z]+)\.pid$/', $filename, $matches);
			$processes[$matches[1]] = $time;
		}

		return $processes;
	}

	/**
	 * Note this does not work from the web backend to kill CLI workers.
	 * We might need to run some exec() kill command here instead.
	 *
	 * @param int $pid
	 * @param int $sig Signal (defaults to graceful SIGTERM = 15)
	 * @return void
	 */
	public function terminateProcess($pid, $sig = SIGTERM) {
		if (!$pid) {
			return;
		}

		$pidFilePath = Configure::read('Queue.pidfilepath');

		$killed = false;
		if (function_exists('posix_kill')) {
			$killed = posix_kill($pid, $sig);
		}
		if (!$killed) {
			exec('kill -' . $sig . ' ' . $pid);
		}
		sleep(1);

		if (!$pidFilePath) {
			$QueueProcesses = TableRegistry::get('Queue.QueueProcesses');
			$QueueProcesses->deleteAll(['pid' => $pid]);
			return;
		}

		// Deprecated file system
		$file = $pidFilePath . 'queue_' . $pid . '.pid';
		if (file_exists($file)) {
			unlink($file);
		}
	}

	/**
	 * get the name of the driver
	 *
	 * @return string
	 */
	protected function _getDriverName() {
		$className = explode('\\', $this->connection()->config()['driver']);
		$name = end($className);

		return $name;
	}

}
