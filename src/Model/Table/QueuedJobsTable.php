<?php
declare(strict_types=1);

namespace Queue\Model\Table;

use ArrayObject;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotImplementedException;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use CakeDto\Dto\FromArrayToArrayInterface;
use InvalidArgumentException;
use Queue\Config\JobConfig;
use Queue\Model\Entity\QueuedJob;
use Queue\Queue\Config;
use Queue\Queue\TaskFinder;
use RuntimeException;
use Search\Manager;

/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @method \Queue\Model\Entity\QueuedJob get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Queue\Model\Entity\QueuedJob newEntity(array $data, array $options = [])
 * @method array<\Queue\Model\Entity\QueuedJob> newEntities(array $data, array $options = [])
 * @method \Queue\Model\Entity\QueuedJob|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Queue\Model\Entity\QueuedJob patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\Queue\Model\Entity\QueuedJob> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Queue\Model\Entity\QueuedJob findOrCreate($search, ?callable $callback = null, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @method \Queue\Model\Entity\QueuedJob saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @mixin \Search\Model\Behavior\SearchBehavior
 * @property \Queue\Model\Table\QueueProcessesTable&\Cake\ORM\Association\BelongsTo $WorkerProcesses
 * @method \Queue\Model\Entity\QueuedJob newEmptyEntity()
 * @method \Cake\Datasource\ResultSetInterface<\Queue\Model\Entity\QueuedJob>|false saveMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Queue\Model\Entity\QueuedJob> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Queue\Model\Entity\QueuedJob>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Queue\Model\Entity\QueuedJob> deleteManyOrFail(iterable $entities, array $options = [])
 */
class QueuedJobsTable extends Table {

	/**
	 * @var string
	 */
	public const DRIVER_MYSQL = 'Mysql';

	/**
	 * @var string
	 */
	public const DRIVER_POSTGRES = 'Postgres';

	/**
	 * @var string
	 */
	public const DRIVER_SQLSERVER = 'Sqlserver';

	/**
	 * @var string
	 */
	public const DRIVER_SQLITE = 'Sqlite';

	/**
	 * @var int
	 */
	public const STATS_LIMIT = 100000;

	/**
	 * @var int
	 */
	public const DAY = 86400;

	/**
	 * @var array<string, string>
	 */
	public array $rateHistory = [];

	/**
	 * @var \Queue\Queue\TaskFinder|null
	 */
	protected ?TaskFinder $taskFinder = null;

	/**
	 * @var string|null
	 */
	protected ?string $_key = null;

	/**
	 * set connection name
	 *
	 * @return string
	 */
	public static function defaultConnectionName(): string {
		$connection = Configure::read('Queue.connection');
		if (!empty($connection)) {
			return $connection;
		}

		return parent::defaultConnectionName();
	}

	/**
	 * initialize Table
	 *
	 * @param array<string, mixed> $config Configuration
	 *
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

		$this->getSchema()->setColumnType('data', 'json');
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \ArrayObject<string, mixed> $data
	 * @param \ArrayObject<string, mixed> $options
	 *
	 * @return void
	 */
	public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void {
		if (isset($data['data']) && $data['data'] === '') {
			$data['data'] = null;
		}
	}

	/**
	 * @return \Search\Manager
	 */
	public function searchManager(): Manager {
		$searchManager = $this->behaviors()->Search->searchManager();
		$searchManager
			->value('job_task')
			->like('search', ['fields' => ['job_group', 'reference', 'status'], 'before' => true, 'after' => true])
			->add('status', 'Search.Callback', [
				'callback' => function (SelectQuery $query, array $args, $filter) {
					$status = $args['status'];
					if ($status === 'completed') {
						$query->where(['completed IS NOT' => null]);

						return true;
					}
					if ($status === 'in_progress') {
						$query->where([
						'completed IS' => null,
						'OR' => [
							'notbefore <=' => new DateTime(),
							'notbefore IS' => null,
						]]);

						return true;
					}
					if ($status === 'scheduled') {
						$query->where(['completed IS' => null, 'notbefore >' => new DateTime()]);

						return true;
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
	 *
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault(Validator $validator): Validator {
		$validator
			->integer('id')
			->allowEmptyString('id', null, 'create');

		$validator
			->requirePresence('job_task', 'create')
			->notEmptyString('job_task');

		$validator
			->greaterThanOrEqual('progress', 0)
			->lessThanOrEqual('progress', 1)
			->allowEmptyString('progress');

		return $validator;
	}

	/**
	 * @return \Queue\Config\JobConfig
	 */
	public function createConfig(): JobConfig {
		return new JobConfig();
	}

	/**
	 * Adds a new job to the queue.
	 *
	 * Config
	 * - priority: 1-10, defaults to 5
	 * - notBefore: Optional date which must not be preceded
	 * - group: Used to group similar QueuedJobs
	 * - reference: An optional reference string
	 * - status: To set an initial status text
	 *
	 * @param string $jobTask Job task name or FQCN.
	 * @param object|array<string, mixed>|null $data Array of data or DTO like object.
	 * @param \Queue\Config\JobConfig|array<string, mixed> $config Config to save along with the job.
	 *
	 * @return \Queue\Model\Entity\QueuedJob Saved job entity
	 */
	public function createJob(string $jobTask, array|object|null $data = null, array|JobConfig $config = []): QueuedJob {
		if (!$config instanceof JobConfig) {
			$config = $this->createConfig()->fromArray($config);
		}

		if ($data instanceof FromArrayToArrayInterface) {
			$data = $data->toArray();
		} elseif (is_object($data) && method_exists($data, 'toArray')) {
			$data = $data->toArray();
		}
		if ($data !== null && !is_array($data)) {
			throw new InvalidArgumentException('Data must be `array|null`, implement `' . FromArrayToArrayInterface::class . '` or provide a `toArray()` method');
		}

		$queuedJob = [
			'job_task' => $this->jobTask($jobTask),
			'data' => $data,
			'notbefore' => $config->hasNotBefore() ? $this->getDateTime($config->getNotBeforeOrFail()) : null,
			'priority' => $config->getPriority(),
		] + $config->toArray();
		if ($queuedJob['priority'] === null) {
			unset($queuedJob['priority']);
		}

		$queuedJob = $this->newEntity($queuedJob);

		return $this->saveOrFail($queuedJob);
	}

	/**
	 * @param class-string<\Queue\Queue\Task>|string $jobType
	 *
	 * @return string
	 */
	protected function jobTask(string $jobType): string {
		if ($this->taskFinder === null) {
			$this->taskFinder = new TaskFinder();
		}

		return $this->taskFinder->resolve($jobType);
	}

	/**
	 * @param string $reference
	 * @param string|null $jobTask
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return bool
	 */
	public function isQueued(string $reference, ?string $jobTask = null): bool {
		if (!$reference) {
			throw new InvalidArgumentException('A reference is needed');
		}

		$conditions = [
			'reference' => $reference,
			'completed IS' => null,
		];
		if ($jobTask) {
			$conditions['job_task'] = $jobTask;
		}

		return (bool)$this->find()->where($conditions)->select(['id'])->first();
	}

	/**
	 * Returns the number of items in the queue.
	 * Either returns the number of ALL pending jobs, or the number of pending jobs of the passed type.
	 *
	 * @param string|null $type Job type to Count
	 *
	 * @return int
	 */
	public function getLength(?string $type = null): int {
		$findConf = [
			'conditions' => [
				'completed IS' => null,
				'OR' => [
					'notbefore <=' => new DateTime(),
					'notbefore IS' => null,
				],
			],
		];
		if ($type !== null) {
			$findConf['conditions']['job_task'] = $type;
		}

		return $this->find('all', ...$findConf)->count();
	}

	/**
	 * Return a list of all job types in the Queue.
	 *
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function getTypes(): SelectQuery {
		$findCond = [
			'fields' => [
				'job_task',
			],
			'group' => [
				'job_task',
			],
			'keyField' => 'job_task',
			'valueField' => 'job_task',
		];

		return $this->find('list', ...$findCond);
	}

	/**
	 * Return some statistics about finished jobs still in the Database.
	 * TO-DO: rewrite as virtual field
	 *
	 * @param bool $disableHydration
	 *
	 * @return array<\Queue\Model\Entity\QueuedJob>|array<mixed>
	 */
	public function getStats(bool $disableHydration = false): array {
		$driverName = $this->getDriverName();
		$options = [
			'fields' => function (SelectQuery $query) use ($driverName) {
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
					case static::DRIVER_SQLITE:
						$alltime = $query->func()->avg('julianday(completed) - julianday(created)');
						$runtime = $query->func()->avg('julianday(completed) - julianday(fetched)');
						$fetchdelay = $query->func()->avg('julianday(fetched) - (CASE WHEN notbefore IS NULL THEN julianday(created) ELSE julianday(notbefore) END)');

						break;
				}

				return [
					'job_task',
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
				'job_task',
			],
		];

		$query = $this->find('all', ...$options);
		if ($disableHydration) {
			$query = $query->disableHydration();
		}
		$result = $query->toArray();
		if ($result && $driverName === static::DRIVER_SQLITE) {
			foreach ($result as $key => $row) {
				$result[$key]['fetchdelay'] = (int)round($row['fetchdelay'] * static::DAY);
				$result[$key]['runtime'] = (int)round($row['runtime'] * static::DAY);
				$result[$key]['alltime'] = (int)round($row['alltime'] * static::DAY);
			}
		}

		return $result;
	}

	/**
	 * Returns [
	 *   'JobType' => [
	 *      'YYYY-MM-DD' => INT,
	 *      ...
	 *   ]
	 * ]
	 *
	 * @param string|null $jobTask
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function getFullStats(?string $jobTask = null): array {
		$driverName = $this->getDriverName();
		$fields = function (SelectQuery $query) use ($driverName) {
			$runtime = $query->newExpr('UNIX_TIMESTAMP(completed) - UNIX_TIMESTAMP(fetched)');
			switch ($driverName) {
				case static::DRIVER_SQLSERVER:
					$runtime = $query->newExpr("DATEDIFF(s, '1970-01-01 00:00:00', completed) - DATEDIFF(s, '1970-01-01 00:00:00', fetched)");

					break;
				case static::DRIVER_POSTGRES:
					$runtime = $query->newExpr('EXTRACT(EPOCH FROM completed) - EXTRACT(EPOCH FROM fetched)');

					break;
				case static::DRIVER_SQLITE:
					$runtime = $query->newExpr('julianday(completed) - julianday(fetched)');

					break;
			}

			return [
				'job_task',
				'created',
				'duration' => $runtime,
			];
		};

		$conditions = ['completed IS NOT' => null];
		if ($jobTask) {
			$conditions['job_task'] = $jobTask;
		}

		$jobs = $this->find()
			->select($fields)
			->where($conditions)
			->enableHydration(false)
			->orderByDesc('id')
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

			$runtime = $job['duration'];
			if ($driverName === static::DRIVER_SQLITE) {
				$runtime = (int)round($runtime * static::DAY);
			}

			/** @var string $name */
			$name = $job['job_task'];
			$result[$name][$day][] = $runtime;
		}

		foreach ($result as $jobTask => $jobs) {
			/**
			 * @var string $day
			 * @var array<int> $durations
			 */
			foreach ($jobs as $day => $durations) {
				$average = array_sum($durations) / count($durations);
				$result[$jobTask][$day] = (int)$average;
			}

			foreach ($days as $day) {
				if (isset($result[$jobTask][$day])) {
					continue;
				}

				$result[$jobTask][$day] = 0;
			}

			ksort($result[$jobTask]);
		}

		return $result;
	}

	/**
	 * Look for a new job that can be processed with the current abilities and
	 * from the specified group (or any if null).
	 *
	 * @param array<string, array<string, mixed>> $tasks Available QueueWorkerTasks.
	 * @param array<string> $groups Request a job from these groups (or exclude certain groups), or any otherwise.
	 * @param array<string> $types Request a job from these types (or exclude certain types), or any otherwise.
	 *
	 * @return \Queue\Model\Entity\QueuedJob|null
	 */
	public function requestJob(array $tasks, array $groups = [], array $types = []): ?QueuedJob {
		$now = $this->getDateTime();
		$nowStr = $now->toDateTimeString();
		$driverName = $this->getDriverName();

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
			case static::DRIVER_SQLITE:
				$age = $query->newExpr()
					->add('IFNULL(CAST(strftime("%s", CURRENT_TIMESTAMP) as integer) - CAST(strftime("%s", "' . $nowStr . '") as integer), 0)');

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
		foreach ($tasks as $name => $task) {
			if (!$task['costs']) {
				continue;
			}

			$costConstraints[$name] = $task['costs'];
		}

		$uniqueConstraints = [];
		foreach ($tasks as $name => $task) {
			if (!$task['unique']) {
				continue;
			}

			$uniqueConstraints[$name] = $name;
		}

		/** @var array<\Queue\Model\Entity\QueuedJob> $runningJobs */
		$runningJobs = [];
		if ($costConstraints || $uniqueConstraints) {
			$constraintJobs = array_keys($costConstraints + $uniqueConstraints);
			$runningJobs = $this->find('queued')
				->contain(['WorkerProcesses'])
				->where(['QueuedJobs.job_task IN' => $constraintJobs, 'QueuedJobs.workerkey IS NOT' => null, 'QueuedJobs.workerkey !=' => $this->_key, 'WorkerProcesses.modified >' => (new DateTime())->subSeconds(Config::defaultworkertimeout())])
				->all()
				->toArray();
		}

		$costs = 0;
		$server = $this->WorkerProcesses->buildServerString();
		foreach ($runningJobs as $runningJob) {
			if (isset($uniqueConstraints[$runningJob->job_task])) {
				$types[] = '-' . $runningJob->job_task;

				continue;
			}

			if ($runningJob->worker_process->server === $server && isset($costConstraints[$runningJob->job_task])) {
				$costs += $costConstraints[$runningJob->job_task];
			}
		}

		if ($costs) {
			$left = 100 - $costs;
			foreach ($tasks as $name => $task) {
				if (!$task['costs'] || $task['costs'] < $left) {
					continue;
				}

				$types[] = '-' . $name;
			}
		}

		if ($groups) {
			$options['conditions'] = $this->addFilter($options['conditions'], 'job_group', $groups);
		}
		if ($types) {
			$options['conditions'] = $this->addFilter($options['conditions'], 'job_task', $types);
		}

		// Generate the task specific conditions.
		foreach ($tasks as $name => $task) {
			$timeoutAt = clone $now;
			$tmp = [
				'job_task' => $name,
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
				'attempts <' => $task['retries'] + 1,
			];
			if (array_key_exists('rate', $task) && $tmp['job_task'] && array_key_exists($tmp['job_task'], $this->rateHistory)) {
				switch ($driverName) {
					case static::DRIVER_POSTGRES:
						$tmp['EXTRACT(EPOCH FROM NOW()) >='] = $this->rateHistory[$tmp['job_task']] + $task['rate'];

						break;
					case static::DRIVER_MYSQL:
						$tmp['UNIX_TIMESTAMP() >='] = $this->rateHistory[$tmp['job_task']] + $task['rate'];

						break;
					case static::DRIVER_SQLSERVER:
						$tmp["(DATEDIFF(s, '1970-01-01 00:00:00', GETDATE())) >="] = $this->rateHistory[$tmp['job_task']] + $task['rate'];

						break;
					case static::DRIVER_SQLITE:
						//TODO

						break;
				}
			}
			$options['conditions']['OR'][] = $tmp;
		}

		/** @var \Queue\Model\Entity\QueuedJob|null $job */
		$job = $this->getConnection()->transactional(function () use ($query, $options, $now, $driverName) {
			$query->find('all', ...$options)->enableAutoFields(true);

			switch ($driverName) {
				case static::DRIVER_MYSQL:
				case static::DRIVER_POSTGRES:
					$query->epilog('FOR UPDATE');

					break;
				case static::DRIVER_SQLSERVER:
					// the ORM does not support ROW locking at the moment
					// TODO

					break;
				case static::DRIVER_SQLITE:
					// not supported

					break;
			}

			/** @var \Queue\Model\Entity\QueuedJob|null $job */
			$job = $query->first();

			if (!$job) {
				return null;
			}

			$key = $this->key();
			$job = $this->patchEntity($job, [
				'workerkey' => $key,
				'fetched' => $now,
				'progress' => null,
				'failure_message' => null,
				'attempts' => $job->attempts + 1,
			]);

			return $this->saveOrFail($job);
		});

		if (!$job) {
			return null;
		}

		$this->rateHistory[$job->job_task] = $now->toUnixString();

		return $job;
	}

	/**
	 * @param int $id ID of job
	 * @param float $progress Value from 0 to 1
	 * @param string|null $status
	 *
	 * @return bool Success
	 */
	public function updateProgress(int $id, float $progress, ?string $status = null): bool {
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
	 *
	 * @return bool Success
	 */
	public function markJobDone(QueuedJob $job): bool {
		$fields = [
			'progress' => 1,
			'completed' => $this->getDateTime(),
		];
		$job = $this->patchEntity($job, $fields);

		return (bool)$this->save($job);
	}

	/**
	 * Mark a job as Failed, without incrementing the "attempts" count due to to it being incremented when fetched.
	 *
	 * @param \Queue\Model\Entity\QueuedJob $job Job
	 * @param string|null $failureMessage Optional message to append to the failure_message field.
	 *
	 * @return bool Success
	 */
	public function markJobFailed(QueuedJob $job, ?string $failureMessage = null): bool {
		$fields = [
			'failure_message' => $failureMessage,
		];
		$job = $this->patchEntity($job, $fields);

		return (bool)$this->save($job);
	}

	/**
	 * Removes all failed jobs.
	 *
	 * @return int Count of deleted rows
	 */
	public function flushFailedJobs(): int {
		$timeout = Config::defaultworkertimeout();
		$thresholdTime = (new DateTime())->subSeconds($timeout);

		$conditions = [
			'completed IS' => null,
			'attempts >' => 0,
			'fetched <' => $thresholdTime,
		];

		return $this->deleteAll($conditions);
	}

	/**
	 * Resets all failed and not yet completed jobs.
	 *
	 * @param int|null $id
	 * @param bool $full Also currently running jobs.
	 *
	 * @return int Success
	 */
	public function reset(?int $id = null, bool $full = false): int {
		$fields = [
			'completed' => null,
			'fetched' => null,
			'progress' => null,
			'attempts' => 0,
			'workerkey' => null,
			'failure_message' => null,
		];
		$conditions = [
			'completed IS' => null,
			'OR' => [
				'notbefore <=' => new DateTime(),
				'notbefore IS' => null,
			],
		];
		if ($id) {
			$conditions['id'] = $id;
		}
		if (!$full) {
			$conditions['attempts >'] = 0;
		}

		return $this->updateAll($fields, $conditions);
	}

	/**
	 * @param string $task
	 * @param string|null $reference
	 *
	 * @return int
	 */
	public function rerunByTask(string $task, ?string $reference = null): int {
		$fields = [
			'completed' => null,
			'fetched' => null,
			'progress' => null,
			'attempts' => 0,
			'workerkey' => null,
			'failure_message' => null,
		];
		$conditions = [
			'completed IS NOT' => null,
			'job_task' => $task,
		];
		if ($reference) {
			$conditions['reference'] = $reference;
		}

		return $this->updateAll($fields, $conditions);
	}

	/**
	 * @param int $id
	 *
	 * @return int
	 */
	public function rerun(int $id): int {
		$fields = [
			'completed' => null,
			'fetched' => null,
			'progress' => null,
			'attempts' => 0,
			'workerkey' => null,
			'failure_message' => null,
		];
		$conditions = [
			'completed IS NOT' => null,
			'id' => $id,
		];

		return $this->updateAll($fields, $conditions);
	}

	/**
	 * Return some statistics about unfinished jobs still in the Database.
	 *
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function getPendingStats(): SelectQuery {
		$findCond = [
			'fields' => [
				'id',
				'job_task',
				'created',
				'status',
				'priority',
				'fetched',
				'progress',
				'reference',
				'notbefore',
				'attempts',
				'failure_message',
			],
			'conditions' => [
				'completed IS' => null,
				'OR' => [
					'notbefore <=' => new DateTime(),
					'notbefore IS' => null,
				],
			],
		];

		return $this->find('all', ...$findCond);
	}

	/**
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function getScheduledStats(): SelectQuery {
		$findCond = [
			'fields' => [
				'id',
				'job_task',
				'created',
				'status',
				'priority',
				'fetched',
				'progress',
				'reference',
				'notbefore',
				'attempts',
				'failure_message',
			],
			'conditions' => [
				'completed IS' => null,
				'notbefore >' => new DateTime(),
			],
		];

		return $this->find('all', ...$findCond);
	}

	/**
	 * Cleanup/Delete Completed Jobs.
	 *
	 * @return int
	 */
	public function cleanOldJobs(): int {
		if (!Configure::read('Queue.cleanuptimeout')) {
			return 0;
		}

		return $this->deleteAll([
			'completed <' => time() - (int)Configure::read('Queue.cleanuptimeout'),
		]);
	}

	/**
	 * @param \Queue\Model\Entity\QueuedJob $queuedTask
	 * @param array<string, array<string, mixed>> $taskConfiguration
	 *
	 * @return string
	 */
	public function getFailedStatus(QueuedJob $queuedTask, array $taskConfiguration): string {
		$failureMessageRequeued = 'requeued';

		$queuedTaskName = 'Queue' . $queuedTask->job_task;
		if (empty($taskConfiguration[$queuedTaskName])) {
			return $failureMessageRequeued;
		}
		$retries = $taskConfiguration[$queuedTaskName]['retries'];
		if ($queuedTask->attempts <= $retries) {
			return $failureMessageRequeued;
		}

		return 'aborted';
	}

	/**
	 * Custom find method, as in `find('queued', ...)`.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query The query to find with
	 * @param array<string, mixed> $options The options to find with
	 *
	 * @return \Cake\ORM\Query\SelectQuery The query builder
	 */
	public function findQueued(SelectQuery $query, array $options = []): SelectQuery {
		return $query->where(['completed IS' => null]);
	}

	/**
	 * //FIXME
	 *
	 * @return void
	 */
	public function clearDoublettes(): void {
		/** @var array<int> $x */
		$x = $this->getConnection()->selectQuery('SELECT max(id) as id FROM `' . $this->getTable() . '`
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
	public function key(): string {
		if ($this->_key !== null) {
			return $this->_key;
		}
		$this->_key = sha1(microtime() . mt_rand(100, 999));
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
	public function clearKey(): void {
		$this->_key = null;
	}

	/**
	 * truncate()
	 *
	 * @return void
	 */
	public function truncate(): void {
		/** @var \Cake\Database\Schema\TableSchema $schema */
		$schema = $this->getSchema();
		$sql = $schema->truncateSql($this->getConnection());
		foreach ($sql as $snippet) {
			$this->getConnection()->execute($snippet);
		}
	}

	/**
	 * get the name of the driver
	 *
	 * @return string
	 */
	protected function getDriverName(): string {
		$className = explode('\\', $this->getConnection()->config()['driver']);
		$name = end($className) ?: '';

		return $name;
	}

	/**
	 * @param array<mixed> $conditions
	 * @param string $key
	 * @param array<string> $values
	 *
	 * @return array<mixed>
	 */
	protected function addFilter(array $conditions, string $key, array $values): array {
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
	 * @param \Cake\I18n\DateTime|string|int|null $notBefore
	 *
	 * @return \Cake\I18n\DateTime
	 */
	protected function getDateTime(DateTime|string|int|null $notBefore = null): DateTime {
		if (is_object($notBefore)) {
			return $notBefore;
		}

		return new DateTime($notBefore);
	}

}
