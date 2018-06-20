<?php
namespace Queue\Model\Table;

use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * QueueProcesses Model
 *
 * @method \Queue\Model\Entity\QueueProcess get($primaryKey, $options = [])
 * @method \Queue\Model\Entity\QueueProcess newEntity($data = null, array $options = [])
 * @method \Queue\Model\Entity\QueueProcess[] newEntities(array $data, array $options = [])
 * @method \Queue\Model\Entity\QueueProcess|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Queue\Model\Entity\QueueProcess patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Queue\Model\Entity\QueueProcess[] patchEntities($entities, array $data, array $options = [])
 * @method \Queue\Model\Entity\QueueProcess findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @method \Queue\Model\Entity\QueueProcess|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 */
class QueueProcessesTable extends Table {

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
	 * Initialize method
	 *
	 * @param array $config The configuration for the Table.
	 * @return void
	 */
	public function initialize(array $config) {
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
	public function validationDefault(Validator $validator) {
		$validator
			->integer('id')
			->allowEmpty('id', 'create');

		$validator
			->requirePresence('pid', 'create')
			->notEmpty('pid');

		return $validator;
	}

	/**
	 * @return \Cake\Orm\Query
	 */
	public function findActive() {
		$timeout = Configure::read('Queue.defaultworkertimeout');
		$thresholdTime = time() - $timeout;

		$query = $this->find()->where(['modified > ' => $thresholdTime]);

		return $query;
	}

	/**
	 * @param string $pid
	 *
	 * @return int
	 */
	public function add($pid) {
		$queueProcess = $this->newEntity([
			'pid' => $pid,
		]);
		$this->saveOrFail($queueProcess);

		return $queueProcess->id;
	}

	/**
	 * @param string $pid
	 *
	 * @return void
	 */
	public function update($pid) {
		$queueProcess = $this->find()->where(['pid' => $pid])->firstOrFail();
		$queueProcess->modified = new FrozenTime();
		$this->saveOrFail($queueProcess);
	}

	/**
	 * @param string $pid
	 *
	 * @return void
	 */
	public function remove($pid) {
		$this->deleteAll(['pid' => $pid]);
	}

	/**
	 * @return void
	 */
	public function cleanKilledProcesses() {
		$timeout = Configure::read('Queue.defaultworkertimeout');
		$thresholdTime = time() - $timeout;

		$this->deleteAll(['modified <' => time() - $thresholdTime]);
	}

}
