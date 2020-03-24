<?php

namespace Queue\Model\Table;

use ArrayObject;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotImplementedException;
use Cake\I18n\FrozenTime;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use InvalidArgumentException;
use Queue\Model\Entity\QueuedJob;
use Queue\Queue\Config;
use RuntimeException;

// PHP 7.1+ has this defined
if (!defined('SIGTERM')) {
	define('SIGTERM', 15);
}

/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @method \Queue\Model\Entity\QueuedJob get($primaryKey, $options = [])
 * @method \Queue\Model\Entity\QueuedJob newEntity(array $data, array $options = [])
 * @method \Queue\Model\Entity\QueuedJob[] newEntities(array $data, array $options = [])
 * @method \Queue\Model\Entity\QueuedJob|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Queue\Model\Entity\QueuedJob patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Queue\Model\Entity\QueuedJob[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Queue\Model\Entity\QueuedJob findOrCreate($search, ?callable $callback = null, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @method \Queue\Model\Entity\QueuedJob saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @mixin \Search\Model\Behavior\SearchBehavior
 * @property \Queue\Model\Table\QueueProcessesTable&\Cake\ORM\Association\BelongsTo $WorkerProcesses
 * @method \Queue\Model\Entity\QueuedJob newEmptyEntity()
 * @method \Queue\Model\Entity\QueuedJob[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Queue\Model\Entity\QueuedJob[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Queue\Model\Entity\QueuedJob[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Queue\Model\Entity\QueuedJob[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class QueuedJobsTable extends Table {

	const DRIVER_MYSQL = 'Mysql';
	const DRIVER_POSTGRES = 'Postgres';
	const DRIVER_SQLSERVER = 'Sqlserver';

	const STATS_LIMIT = 100000;

	/**
	 * @var array
	 */
	public $rateHistory = [];

	/**
	 * @var string|null
	 */
	protected $_key;

	/**
	 * set connection name
	 *
	 * @return string
	 */
	public static function defaultConnectionName(): string {
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
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->addBehavior('Timestamp');
		if (Configure::read('Queue.isSearchEnabled') !== false && Plugin::isLoaded('Search')) {
			$this->addBehavior('Search.Search');
		}

		$this->belongsTo('WorkerProcesses', [
			'className' => 'Queue.QueueProcesses',
			'foreignKey' => false,
			'conditions' => [
				'WorkerProcesses.workerkey = QueuedJobs.workerkey',
			],
		]);
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \ArrayObject $data
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options) {
		if (isset($data['data']) && $data['data'] === '') {
			$data['data'] = null;
		}
	}

	/**
	 * @return \Search\Manager
	 */
	public function searchManager() {
		$searchManager = $this->behaviors()->Search->searchManager();
		$searchManager
			->value('job_type')
			->like('search', ['fields' => ['job_group', 'reference'], 'before' => true, 'after' => true])
			->add('status', 'Search.Callback', [
				'callback' => function (Query $query, array $args, $filter) {
					$status = $args['status'];
					if ($status === 'completed') {
						$query->where(['completed IS NOT' => null]);

						return $query;
					}
					if ($status === 'in_progress') {
						$query->where(['completed IS' => null]);

						return $query;
					}

					throw new NotImplementedException('Invalid status type');
				},
			]);

		return $searchManager;
	}

	/**
	 * Default validation rules.
	 *
	 * @param \Cake\Validation\Validator $validator Validator instance.
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault(Validator $validator): Validator {
		$validator
			->integer('id')
			->allowEmptyString('id', null, 'create');

		$validator
			->requirePresence('job_type', 'create')
			->notEmptyString('job_type');

		return $validator;
	}

	/**
	 * Adds a new job to the queue.
	 *
	 * Config
	 * - priority: 1-10, defaults to 5
	 * - notBefore: Optional date which must not be preceded
	 * - group: Used to group similar QueuedJobs
	 * - reference: An optional reference string
	 *
	 * @param string $jobType Job name
	 * @param array|null $data Array of data
	 * @param array $config Config to save along with the job
	 * @return \Queue\Model\Entity\QueuedJob Saved job entity
	 */
	public function createJob($jobType, array $data = null, array $config = []) {
		$queuedJob = [
			'job_type' => $jobType,
			'data' => is_array($data) ? serialize($data) : null,
			'job_group' => !empty($config['group']) ? $config['group'] : null,
			'notbefore' => !empty($config['notBefore']) ? $this->getDateTime($config['notBefore']) : null,
		] + $config;

		$queuedJob = $this->newEntity($queuedJob);

		return $this->saveOrFail($queuedJob);
	}

	/**
	 * @param string $reference
	 * @param string|null $jobType
	 *
	 * @return bool
	 *
	 * @throws \InvalidArgumentException
	 */
	public function isQueued($reference, $jobType = null) {
		if (!$reference) {
			throw new InvalidArgumentException('A reference is needed');
		}

		$conditions = [
			'reference' => $reference,
			'completed IS' => null,
		];
		if ($jobType) {
			$conditions['job_type'] = $jobType;
		}

		return (bool)$this->find()->where($conditions)->select(['id'])->first();
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

		return $this->find('all', $findConf)->count();
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
			'fields' => function (Query $query) use ($driverName) {
				$alltime = $query->func()->avg('UNIX_TIMESTAMP(completed) - UNIX_TIMESTAMP(created)');
				$runtime = $query->func()->avg('UNIX_TIMESTAMP(completed) - UNIX_TIMESTAMP(fetched)');
				$fetchdelay = $query->func()->avg('UNIX_TIMESTAMP(fetched) - IF(notbefore is NULL, UNIX_TIMESTAMP(created), UNIX_TIMESTAMP(notbefore))');
				switch ($driverName) {
					case static::DRIVER_SQLSERVER:
						$alltime = $query->func()->avg("DATEDIFF(s, '1970-01-01 00:00:00', completed) - DATEDIFF(s, '1970-01-01 00:00:00', created)");
						$runtime = $query->func()->avg("DATEDIFF(s, '1970-01-01 00:00:00', completed) - DATEDIFF(s, '1970-01-01 00:00:00', fetched)");
						$fetchdelay = $query->func()->avg("DATEDIFF(s, '1970-01-01 00:00:00', fetched) - (CASE WHEN notbefore IS NULL THEN DATEDIFF(s, '1970-01-01 00:00:00', created) ELSE DATEDIFF(s, '1970-01-01 00:00:00', notbefore) END)");
						break;
					case static::DRIVER_POSTGRES:
						$alltime = $query->func()->avg('EXTRACT(EPOCH FROM completed) - EXTRACT(EPOCH FROM created)');
						$runtime = $query->func()->avg('EXTRACT(EPOCH FROM completed) - EXTRACT(EPOCH FROM fetched)');
						$fetchdelay = $query->func()->avg('EXTRACT(EPOCH FROM fetched) - CASE WHEN notbefore IS NULL then EXTRACT(EPOCH FROM created) ELSE EXTRACT(EPOCH FROM notbefore) END');
						break;
				}

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
	 * Returns [
	 *   'JobType' => [
	 *      'YYYY-MM-DD' => INT,
	 *      ...
	 *   ]
	 * ]
	 *
	 * @param string|null $jobType
	 * @return array
	 */
	public function getFullStats($jobType = null) {
		$driverName = $this->_getDriverName();
		$fields = function (Query $query) use ($driverName) {
			$runtime = $query->newExpr('UNIX_TIMESTAMP(completed) - UNIX_TIMESTAMP(fetched)');
			switch ($driverName) {
				case static::DRIVER_SQLSERVER:
					$runtime = $query->newExpr("DATEDIFF(s, '1970-01-01 00:00:00', completed) - DATEDIFF(s, '1970-01-01 00:00:00', fetched)");
					break;
				case static::DRIVER_POSTGRES:
					$runtime = $query->newExpr('EXTRACT(EPOCH FROM completed) - EXTRACT(EPOCH FROM fetched)');
					break;
			}

			return [
				'job_type',
				'created',
				'duration' => $runtime,
			];
		};

		$conditions = ['completed IS NOT' => null];
		if ($jobType) {
			$conditions['job_type'] = $jobType;
		}

		$jobs = $this->find()
			->select($fields)
			->where($conditions)
			->enableHydration(false)
			->orderDesc('id')
			->limit(static::STATS_LIMIT)
			->all()
			->toArray();

		$result = [];

		$days = [];

		foreach ($jobs as $job) {
			/** @var \DateTime $created */
			$created = $job['created'];
			$day = $created->format('Y-m-d');
			if (!isset($days[$day])) {
				$days[$day] = $day;
			}

			$result[$job['job_type']][$day][] = $job['duration'];
		}

		foreach ($result as $jobType => $jobs) {
			foreach ($jobs as $day => $durations) {
				$average = array_sum($durations) / count($durations);
				$result[$jobType][$day] = (int)$average;
			}

			foreach ($days as $day) {
				if (isset($result[$jobType][$day])) {
					continue;
				}

				$result[$jobType][$day] = 0;
			}

			ksort($result[$jobType]);
		}

		return $result;
	}

	/**
	 * Look for a new job that can be processed with the current abilities and
	 * from the specified group (or any if null).
	 *
	 * @param array $capabilities Available QueueWorkerTasks.
	 * @param string[] $groups Request a job from these groups (or exclude certain groups), or any otherwise.
	 * @param string[] $types Request a job from these types (or exclude certain types), or any otherwise.
	 * @return \Queue\Model\Entity\QueuedJob|null
	 */
	public function requestJob(array $capabilities, array $groups = [], array $types = []) {
		$now = $this->getDateTime();
		$nowStr = $now->toDateTimeString();
		$driverName = $this->_getDriverName();

		$query = $this->find();
		$age = $query->newExpr()->add('IFNULL(TIMESTAMPDIFF(SECOND, "' . $nowStr . '", notbefore), 0)');
		switch ($driverName) {
			case static::DRIVER_SQLSERVER:
				$age = $query->newExpr()->add('ISNULL(DATEDIFF(SECOND, GETDATE(), notbefore), 0)');
				break;
			case static::DRIVER_POSTGRES:
				$age = $query->newExpr()
					->add('COALESCE(EXTRACT(EPOCH FROM notbefore) - (EXTRACT(EPOCH FROM now())), 0)');
				break;
		}
		$options = [
			'conditions' => [
				'completed IS' => null,
				'OR' => [],
			],
			'fields' => [
				'age' => $age,
			],
			'order' => [
				'priority' => 'ASC',
				'age' => 'ASC',
				'id' => 'ASC',
			],
		];

		$costConstraints = [];
		foreach ($capabilities as $capability) {
			if (!$capability['costs']) {
				continue;
			}

			$costConstraints[$capability['name']] = $capability['costs'];
		}

		$uniqueConstraints = [];
		foreach ($capabilities as $capability) {
			if (!$capability['unique']) {
				continue;
			}

			$uniqueConstraints[$capability['name']] = $capability['name'];
		}

		/** @var \Queue\Model\Entity\QueuedJob[] $runningJobs */
		$runningJobs = [];
		if ($costConstraints || $uniqueConstraints) {
			$constraintJobs = array_keys($costConstraints + $uniqueConstraints);
			$runningJobs = $this->find('queued')
				->contain(['WorkerProcesses'])
				->where(['QueuedJobs.job_type IN' => $constraintJobs, 'QueuedJobs.workerkey IS NOT' => null, 'QueuedJobs.workerkey !=' => $this->_key, 'WorkerProcesses.modified >' => Config::defaultworkertimeout()])
				->all()
				->toArray();
		}

		$costs = 0;
		$server = $this->WorkerProcesses->buildServerString();
		foreach ($runningJobs as $runningJob) {
			if (isset($uniqueConstraints[$runningJob->job_type])) {
				$types[] = '-' . $runningJob->job_type;
				continue;
			}

			if ($runningJob->worker_process->server === $server && isset($costConstraints[$runningJob->job_type])) {
				$costs += $costConstraints[$runningJob->job_type];
			}
		}

		if ($costs) {
			$left = 100 - $costs;
			foreach ($capabilities as $capability) {
				if (!$capability['costs'] || $capability['costs'] < $left) {
					continue;
				}

				$types[] = '-' . $capability['name'];
			}
		}

		if ($groups) {
			$options['conditions'] = $this->addFilter($options['conditions'], 'job_group', $groups);
		}
		if ($types) {
			$options['conditions'] = $this->addFilter($options['conditions'], 'job_type', $types);
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
							'notbefore <' => $nowStr,
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
						$tmp['EXTRACT(EPOCH FROM NOW()) >='] = $this->rateHistory[$tmp['job_type']] + $task['rate'];
						break;
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

		/** @var \Queue\Model\Entity\QueuedJob|null $job */
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
				'fetched' => $now,
				'progress' => null,
				'failure_message' => null,
			]);

			return $this->saveOrFail($job);
		});

		if (!$job) {
			return null;
		}

		$this->rateHistory[$job->job_type] = $now->toUnixString();

		return $job;
	}

	/**
	 * @param int $id ID of job
	 * @param float $progress Value from 0 to 1
	 * @param string|null $status
	 * @return bool Success
	 */
	public function updateProgress($id, $progress, $status = null) {
		if (!$id) {
			return false;
		}

		$values = [
			'progress' => round($progress, 2),
		];
		if ($status !== null) {
			$values['status'] = $status;
		}

		return (bool)$this->updateAll($values, ['id' => $id]);
	}

	/**
	 * Mark a job as Completed, removing it from the queue.
	 *
	 * @param \Queue\Model\Entity\QueuedJob $job Job
	 * @return bool Success
	 */
	public function markJobDone(QueuedJob $job) {
		$fields = [
			'progress' => 100,
			'completed' => $this->getDateTime(),
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
	 * Resets all failed and not yet completed jobs.
	 *
	 * @param int|null $id
	 * @param bool $full Also currently running jobs.
	 *
	 * @return int Success
	 */
	public function reset($id = null, $full = false) {
		$fields = [
			'completed' => null,
			'fetched' => null,
			'progress' => null,
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
		if (!$full) {
			$conditions['failed >'] = 0;
		}

		return $this->updateAll($fields, $conditions);
	}

	/**
	 * @param string $type
	 * @param string|null $reference
	 *
	 * @return int
	 */
	public function rerun($type, $reference = null) {
		$fields = [
			'completed' => null,
			'fetched' => null,
			'progress' => null,
			'failed' => 0,
			'workerkey' => null,
			'failure_message' => null,
		];
		$conditions = [
			'completed IS NOT' => null,
			'job_type' => $type,
		];
		if ($reference) {
			$conditions['reference'] = $reference;
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
				'priority',
				'fetched',
				'progress',
				'reference',
				'notbefore',
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
			'completed <' => time() - (int)Configure::read('Queue.cleanuptimeout'),
		]);
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
	 * Custom find method, as in `find('queued', ...)`.
	 *
	 * @param \Cake\ORM\Query $query The query to find with
	 * @param array $options The options to find with
	 * @return \Cake\ORM\Query The query builder
	 */
	public function findQueued(Query $query, array $options) {
		return $query->where(['completed IS' => null]);
	}

	/**
	 * Custom find method, as in `find('progress', ...)`.
	 *
	 * @deprecated Unused right now, needs fixing.
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
		/** @var array $x */
		$x = $this->_connection->query('SELECT max(id) as id FROM `' . $this->getTable() . '`
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
		if (!$this->_key) {
			throw new RuntimeException('Invalid key generated');
		}

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
		/** @var \Cake\Database\Schema\TableSchema $schema */
		$schema = $this->getSchema();
		$sql = $schema->truncateSql($this->_connection);
		foreach ($sql as $snippet) {
			$this->_connection->execute($snippet);
		}
	}

	/**
	 * Gets all active processes.
	 *
	 * $forThisServer only works for DB approach.
	 *
	 * @param bool $forThisServer
	 * @return array
	 */
	public function getProcesses($forThisServer = false) {
		/** @var \Queue\Model\Table\QueueProcessesTable $QueueProcesses */
		$QueueProcesses = TableRegistry::getTableLocator()->get('Queue.QueueProcesses');
		$query = $QueueProcesses->findActive()
			->where(['terminate' => false]);
		if ($forThisServer) {
			$query = $query->where(['server' => $QueueProcesses->buildServerString()]);
		}

		$processes = $query
			->enableHydration(false)
			->find('list', ['keyField' => 'pid', 'valueField' => 'modified'])
			->all()
			->toArray();

		return $processes;
	}

	/**
	 * Soft ending of a running job, e.g. when migration is starting
	 *
	 * @param int|null $pid
	 * @return void
	 */
	public function endProcess($pid) {
		if (!$pid) {
			return;
		}

		$QueueProcesses = TableRegistry::getTableLocator()->get('Queue.QueueProcesses');
		/** @var \Queue\Model\Entity\QueueProcess $queuedProcess */
		$queuedProcess = $QueueProcesses->find()->where(['pid' => $pid])->firstOrFail();
		$queuedProcess->terminate = true;
		$QueueProcesses->saveOrFail($queuedProcess);
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

		$killed = false;
		if (function_exists('posix_kill')) {
			$killed = posix_kill($pid, $sig);
		}
		if (!$killed) {
			exec('kill -' . $sig . ' ' . $pid);
		}
		sleep(1);

		$QueueProcesses = TableRegistry::getTableLocator()->get('Queue.QueueProcesses');
		$QueueProcesses->deleteAll(['pid' => $pid]);
	}

	/**
	 * get the name of the driver
	 *
	 * @return string
	 */
	protected function _getDriverName() {
		$className = explode('\\', $this->getConnection()->config()['driver']);
		$name = end($className);

		return $name;
	}

	/**
	 * @param array $conditions
	 * @param string $key
	 * @param string[] $values
	 * @return array
	 */
	protected function addFilter(array $conditions, $key, array $values) {
		$include = [];
		$exclude = [];
		foreach ($values as $value) {
			if (substr($value, 0, 1) === '-') {
				$exclude[] = substr($value, 1);
			} else {
				$include[] = $value;
			}
		}

		if ($include) {
			$conditions[$key . ' IN'] = array_unique($include);
		}
		if ($exclude) {
			$conditions[$key . ' NOT IN'] = array_unique($exclude);
		}

		return $conditions;
	}

	/**
	 * Returns a DateTime object from different input.
	 *
	 * Without argument this will be "now".
	 *
	 * @param int|string|\Cake\I18n\FrozenTime|\Cake\I18n\Time|null $notBefore
	 *
	 * @return \Cake\I18n\FrozenTime|\Cake\I18n\Time
	 */
	protected function getDateTime($notBefore = null) {
		if (is_object($notBefore)) {
			return $notBefore;
		}

		return new FrozenTime($notBefore);
	}

}
