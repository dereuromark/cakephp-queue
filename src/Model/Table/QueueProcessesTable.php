<?php
declare(strict_types=1);

namespace Queue\Model\Table;

use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Queue\Model\ProcessEndingException;
use Queue\Queue\Config;

/**
 * QueueProcesses Model
 *
 * @method \Cake\ORM\Locator\TableLocator getTableLocator()
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @method \Queue\Model\Entity\QueueProcess get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Queue\Model\Entity\QueueProcess newEntity(array $data, array $options = [])
 * @method array<\Queue\Model\Entity\QueueProcess> newEntities(array $data, array $options = [])
 * @method \Queue\Model\Entity\QueueProcess|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Queue\Model\Entity\QueueProcess patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\Queue\Model\Entity\QueueProcess> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Queue\Model\Entity\QueueProcess findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \Queue\Model\Entity\QueueProcess saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Queue\Model\Entity\QueueProcess newEmptyEntity()
 * @method \Cake\Datasource\ResultSetInterface<\Queue\Model\Entity\QueueProcess>|false saveMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Queue\Model\Entity\QueueProcess> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Queue\Model\Entity\QueueProcess>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Queue\Model\Entity\QueueProcess> deleteManyOrFail(iterable $entities, array $options = [])
 * @property \Queue\Model\Table\QueuedJobsTable&\Cake\ORM\Association\HasOne $CurrentQueuedJobs
 */
class QueueProcessesTable extends Table {

	use LocatorAwareTrait;

	/**
	 * Sets connection name
	 *
	 * @return string
	 */
	public static function defaultConnectionName(): string {
		/** @var string|null $connection */
		$connection = Configure::read('Queue.connection');
		if ($connection) {
			return $connection;
		}

		return parent::defaultConnectionName();
	}

	/**
	 * Initialize method
	 *
	 * @param array<string, mixed> $config The configuration for the Table.
	 *
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->setTable('queue_processes');
		$this->setDisplayField('pid');
		$this->setPrimaryKey('id');

		$this->addBehavior('Timestamp');

		$this->hasOne('CurrentQueuedJobs', [
			'className' => 'Queue.QueuedJobs',
			'foreignKey' => 'workerkey',
			'bindingKey' => 'workerkey',
			'propertyName' => 'jobs',
			'conditions' => [
				'QueuedJobs.completed IS NULL',
			],
		]);


		$this->hasOne('ActiveQueuedJob', [
			'className' => 'Queue.QueuedJobs',
			'foreignKey' => 'id',
			'bindingKey' => 'active_job_id',
			'propertyName' => 'active_job'
		]);
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
			->requirePresence('pid', 'create')
			->notEmptyString('pid');

		$validator
			->requirePresence('workerkey', 'create')
			->notEmptyString('workerkey');

		$validator
			->add('server', 'validateCount', [
				'rule' => 'validateCount',
				'provider' => 'table',
				'message' => 'Too many workers running. Check your `Queue.maxworkers` config.',
			]);

		return $validator;
	}

	/**
	 * @param string $value
	 * @param array<string, mixed> $context
	 *
	 * @return string|bool
	 */
	public function validateCount(string $value, array $context) {
		$maxWorkers = Config::maxworkers();
		if (!$value || !$maxWorkers) {
			return true;
		}

		$currentWorkers = $this->find()->where(['server' => $value])->count();
		if ($currentWorkers >= $maxWorkers) {
			return 'Too many workers running (' . $currentWorkers . '/' . $maxWorkers . '). Check your `Queue.maxworkers` config.';
		}

		return true;
	}

	/**
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findActive(): SelectQuery {
		$timeout = Config::defaultworkertimeout();
		$thresholdTime = (new DateTime())->subSeconds($timeout);

		return $this->find()->where(['modified >' => $thresholdTime]);
	}

	/**
	 * Returns the processes for this server in multi-server environment
	 * otherwise, return all
	 * @return SelectQuery
	 */
	public function findForServer(): SelectQuery {
		if (Configure::read('Queue.multiserver')) {
			return $this->find()->where(['server' => $this->buildServerString()]);
		}

		return $this->find();
	}

	/**
	 * @param string $pid
	 * @param string $key
	 *
	 * @return int
	 */
	public function add(string $pid, string $key, string $arguments = NULL): int {
		$data = [
			'pid' => $pid,
			'server' => $this->buildServerString(),
			'workerkey' => $key,
			'arguments' => $arguments,
		];

		$queueProcess = $this->newEntity($data);
		$this->saveOrFail($queueProcess);

		return $queueProcess->id;
	}

	/**
	 * @param string $pid
	 *
	 * @throws \Queue\Model\ProcessEndingException
	 *
	 * @return void
	 */
	public function update(string $pid, int $jobId = NULL): void {
		$conditions = [
			'pid' => $pid,
			'server IS' => $this->buildServerString(),
		];

		/** @var \Queue\Model\Entity\QueueProcess $queueProcess */
		$queueProcess = $this->find()->where($conditions)->firstOrFail();
		if ($queueProcess->terminate) {
			throw new ProcessEndingException('PID terminated: ' . $pid);
		}

		$queueProcess->modified = new DateTime();
		$queueProcess->active_job_id = $jobId;

		$this->saveOrFail($queueProcess);
	}

	/**
	 * @param string $pid
	 *
	 * @return void
	 */
	public function remove(string $pid): void {
		$conditions = [
			'pid' => $pid,
			'server IS' => $this->buildServerString(),
		];

		$this->deleteAll($conditions);
	}

	/**
	 * @return int
	 */
	public function cleanEndedProcesses(): int {
		$timeout = Config::defaultworkertimeout();
		$thresholdTime = (new DateTime())->subSeconds($timeout);

		return $this->deleteAll(['modified <' => $thresholdTime]);
	}

	/**
	 * Remove QueueProcesses which have not an active PID attached
	 * and Terminates processes which are running out of maximum running time
	 * @return int
	 */
	public function clearProcesses(): int {
		$timeout = Config::defaultworkertimeout();
		$thresholdTime = (new DateTime())->subSeconds($timeout);

		$cleaned_processed = 0;
		foreach ($this->findForServer() as $process) {
			if ($this->isProcessRunning($process->pid) === FALSE || $process->modified->getTimestamp() < $thresholdTime->getTimestamp()) {

				$this->terminateProcess($process->pid);

				if (!is_null($process->active_job_id)) {
					$job = $this->CurrentQueuedJobs->findById($process->active_job_id)->first();

					if ($job && is_null($job->completed)) {
						$this->CurrentQueuedJobs->reset($job->id, TRUE);
					}
				}
				$cleaned_processed++;
			}
		}

		return $cleaned_processed;
	}

	/**
	 * If pid logging is enabled, will return an array with
	 * - time: Timestamp as DateTime object
	 * - workers: int Count of currently running workers
	 *
	 * @return array<string, mixed>
	 */
	public function status(): array {
		$timeout = Config::defaultworkertimeout();
		$thresholdTime = (new DateTime())->subSeconds($timeout);

		$results = $this->find()
			->where(['modified >' => $thresholdTime])
			->orderByDesc('modified')
			->enableHydration(false)
			->all()
			->toArray();

		if (!$results) {
			return [];
		}

		$count = count($results);
		$record = array_shift($results);
		/** @var \Cake\I18n\DateTime $time */
		$time = $record['modified'];

		return [
			'time' => $time,
			'workers' => $count,
		];
	}

	/**
	 * Gets all active processes.
	 *
	 * $forThisServer only works for DB approach.
	 *
	 * @param bool $forThisServer
	 *
	 * @return array<\Queue\Model\Entity\QueueProcess>
	 */
	public function getProcesses(bool $forThisServer = false): array {
		/** @var \Queue\Model\Table\QueueProcessesTable $QueueProcesses */
		$QueueProcesses = $this->getTableLocator()->get('Queue.QueueProcesses');
		$query = $QueueProcesses->findActive()
			->contain(['CurrentQueuedJobs'])
			->where(['terminate' => false]);
		if ($forThisServer) {
			$query = $query->where(['server' => $QueueProcesses->buildServerString()]);
		}

		$processes = $query
			->all()
			->toArray();

		return $processes;
	}

	/**
	 * Soft ending of a running job, e.g. when migration is starting
	 *
	 * @param string $pid
	 *
	 * @return void
	 */
	public function endProcess(string $pid): void {
		if (!$pid) {
			return;
		}

		/** @var \Queue\Model\Entity\QueueProcess $queuedProcess */
		$queuedProcess = $this->find()->where(['pid' => $pid])->firstOrFail();
		$queuedProcess->terminate = true;
		$this->saveOrFail($queuedProcess);
	}

	/**
	 * Note this does not work from the web backend to kill CLI workers.
	 * We might need to run some exec() kill command here instead.
	 *
	 * @param string $pid
	 * @param int $sig Signal (defaults to graceful SIGTERM = 15)
	 *
	 * @return void
	 */
	public function terminateProcess(string $pid, int $sig = SIGTERM): void {
		if (!$pid) {
			return;
		}

		$killed = false;
		if (function_exists('posix_kill')) {
			$killed = posix_kill((int)$pid, $sig);
		}
		if (!$killed) {
			exec('kill -' . $sig . ' ' . $pid);
		}

		sleep(1);

		if ($this->isProcessRunning((int)$pid) !== TRUE) {
			$this->deleteAll(['pid' => $pid]);
		};
	}

	/**
	 * Sends a SIGUSR1 to all workers. This will only affect workers
	 * running with config option canInterruptSleep set to true.
	 *
	 * @return void
	 */
	public function wakeUpWorkers(): void {
		if (!function_exists('posix_kill')) {
			return;
		}
		$processes = $this->getProcesses(true);
		foreach ($processes as $process) {
			$pid = (int)$process->pid;
			if ($pid > 0) {
				posix_kill($pid, SIGUSR1);
			}
		}
	}

	/**
	 * Use ENV to control the server name of the servers run workers with.
	 *
	 * export SERVER_NAME=myserver1
	 *
	 * This way you can deploy separately and only end the processes of that server.
	 *
	 * @return string|null
	 */
	public function buildServerString(): ?string {
		$serverName = (string)env('SERVER_NAME') ?: gethostname();
		if (!$serverName) {
			$user = env('USER');
			$logName = env('LOGNAME');
			if ($user || $logName) {
				$serverName = $user . '@' . $logName;
			}
		}

		return $serverName ?: null;
	}

	/**
	 * Checks if it is determinable to check if process is running.
	 * If not, it will return NULL
	 * @param string $pid
	 * @return NULL|bool
	 */
	public function isProcessRunning (string $pid) : ?bool
	{
		if (function_exists('posix_getpgid')) {
			return posix_getpgid((int)$pid) !== FALSE;
		}

		if (is_dir('/proc/')) {
			return file_exists('/proc/' . $pid);
		}

		return NULL;
	}

}
