<?php

namespace Queue\Model\Table;

use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Queue\Model\ProcessEndingException;
use Queue\Queue\Config;

/**
 * QueueProcesses Model
 *
 * @method \Queue\Model\Entity\QueueProcess get($primaryKey, $options = [])
 * @method \Queue\Model\Entity\QueueProcess newEntity(array $data, array $options = [])
 * @method \Queue\Model\Entity\QueueProcess[] newEntities(array $data, array $options = [])
 * @method \Queue\Model\Entity\QueueProcess|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Queue\Model\Entity\QueueProcess patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Queue\Model\Entity\QueueProcess[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Queue\Model\Entity\QueueProcess findOrCreate($search, ?callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @method \Queue\Model\Entity\QueueProcess saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Queue\Model\Entity\QueueProcess newEmptyEntity()
 * @method \Queue\Model\Entity\QueueProcess[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Queue\Model\Entity\QueueProcess[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Queue\Model\Entity\QueueProcess[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Queue\Model\Entity\QueueProcess[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class QueueProcessesTable extends Table {

	/**
	 * Sets connection name
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
	 * Initialize method
	 *
	 * @param array $config The configuration for the Table.
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->setTable('queue_processes');
		$this->setDisplayField('pid');
		$this->setPrimaryKey('id');

		$this->addBehavior('Timestamp');
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
	 * @param array $context
	 *
	 * @return bool
	 */
	public function validateCount($value, array $context) {
		$maxWorkers = Config::maxworkers();
		if (!$value || !$maxWorkers) {
			return true;
		}

		$currentWorkers = $this->find()->where(['server' => $value])->count();
		if ($currentWorkers >= $maxWorkers) {
			return false;
		}

		return true;
	}

	/**
	 * @return \Cake\ORM\Query
	 */
	public function findActive() {
		$timeout = Config::defaultworkertimeout();
		$thresholdTime = (new FrozenTime())->subSeconds($timeout);

		return $this->find()->where(['modified > ' => $thresholdTime]);
	}

	/**
	 * @param string $pid
	 * @param string $key
	 *
	 * @return int
	 */
	public function add($pid, $key) {
		$data = [
			'pid' => $pid,
			'server' => $this->buildServerString(),
			'workerkey' => $key,
		];

		$queueProcess = $this->newEntity($data);
		$this->saveOrFail($queueProcess);

		return $queueProcess->id;
	}

	/**
	 * @param string $pid
	 * @return void
	 * @throws \Queue\Model\ProcessEndingException
	 */
	public function update($pid) {
		$conditions = [
			'pid' => $pid,
			'server IS' => $this->buildServerString(),
		];

		/** @var \Queue\Model\Entity\QueueProcess $queueProcess */
		$queueProcess = $this->find()->where($conditions)->firstOrFail();
		if ($queueProcess->terminate) {
			throw new ProcessEndingException('PID terminated: ' . $pid);
		}

		$queueProcess->modified = new FrozenTime();
		$this->saveOrFail($queueProcess);
	}

	/**
	 * @param string $pid
	 *
	 * @return void
	 */
	public function remove($pid) {
		$conditions = [
			'pid' => $pid,
			'server IS' => $this->buildServerString(),
		];

		$this->deleteAll($conditions);
	}

	/**
	 * @return int
	 */
	public function cleanEndedProcesses() {
		$timeout = Config::defaultworkertimeout();
		$thresholdTime = (new FrozenTime())->subSeconds($timeout);

		return $this->deleteAll(['modified <' => $thresholdTime]);
	}

	/**
	 * If pid logging is enabled, will return an array with
	 * - time: Timestamp as FrozenTime object
	 * - workers: int Count of currently running workers
	 *
	 * @return array
	 */
	public function status() {
		$timeout = Config::defaultworkertimeout();
		$thresholdTime = (new FrozenTime())->subSeconds($timeout);

		$results = $this->find()
			->where(['modified >' => $thresholdTime])
			->orderDesc('modified')
			->enableHydration(false)
			->all()
			->toArray();

		if (!$results) {
			return [];
		}

		$count = count($results);
		$record = array_shift($results);
		/** @var \Cake\I18n\FrozenTime $time */
		$time = $record['modified'];

		return [
			'time' => $time,
			'workers' => $count,
		];
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
	public function buildServerString() {
		$serverName = env('SERVER_NAME') ?: gethostname();
		if (!$serverName) {
			$user = env('USER');
			$logName = env('LOGNAME');
			if ($user || $logName) {
				$serverName = $user . '@' . $logName;
			}
		}

		return $serverName ?: null;
	}

}
