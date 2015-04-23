<?php
App::uses('QueueAppModel', 'Queue.Model');

/**
 * CronTask for cronjobs.
 *
 */
class CronTask extends QueueAppModel {

	public $rateHistory = [];

	public $exit = false;

	public $findMethods = [
		'progress' => true
	];

	public $displayField = 'title';

	public $order = ['CronTask.created' => 'DESC'];

	public $validate = [
		'jobtype' => [
			'notempty' => [
				'rule' => ['notempty'],
				'message' => 'valErrMandatoryField',
			],
		],
		'name' => [
			'notempty' => [
				'rule' => ['notempty'],
				'message' => 'valErrMandatoryField',
				'last' => true
			],
			'isUnique' => [
				'rule' => ['isUnique'],
				'message' => 'valErrRecordNameExists',
				'last' => true
			],
		],
		'title' => [
			'notempty' => [
				'rule' => ['notempty'],
				'message' => 'valErrMandatoryField',
				'last' => true
			],
			'isUnique' => [
				'rule' => ['isUnique'],
				'message' => 'valErrRecordNameExists',
				'last' => true
			],
		],
		'failed' => [
			'numeric' => [
				'rule' => ['numeric'],
				'message' => 'valErrMandatoryField',
			],
		],
		'status' => [
			'numeric' => [
				'rule' => ['numeric'],
				'message' => 'valErrMandatoryField',
			],
		],
		'interval' => [
			'numeric' => [
				'rule' => ['numeric'],
				'message' => 'valErrMandatoryField',
				'last' => true
			],
			'numeric' => [
				'rule' => ['range', 1, 8900000],
				'message' => 'valErrInvalidRange',
				'last' => true
			],
		],
	];

/**
 * Add a new Job to the Queue
 *
 * @param string $jobName QueueTask name
 * @param array $data any array
 * @param string $notBefore notbefore time
 * @param string $group Used to group similar CronTasks
 * @param string $reference any array
 * @return bool Success
 */
	public function createJob($jobName, $data, $notBefore = null, $group = null, $reference = null) {
		$data = [
			'jobtype' => $jobName,
			'data' => serialize($data),
			'group' => $group,
			'reference' => $reference
		];
		if ($notBefore != null) {
			$data['notbefore'] = date('Y-m-d H:i:s', strtotime($notBefore));
		}
		$this->create();
		return $this->save($data);
	}

/**
 * onError handler
 *
 * @return void
 */
	public function onError() {
		$this->exit = true;
	}

/**
 * Look for a new job that can be processed with the current abilities and
 * from the specified group (or any if null).
 *
 * @param array $capabilities Available QueueWorkerTasks.
 * @param string $group Request a job from this group, (from any group if null)
 * @return array Taskdata.
 */
	public function requestJob($capabilities, $group = null) {
		$idlist = [];
		$wasFetched = [];

		$findConf = [
			'conditions' => [
				'completed' => null,
				'OR' => []
			],
			'fields' => [
				'id',
				'fetched',
				'timediff(NOW(),notbefore) AS age'
			],
			'order' => [
				'age DESC',
				'id ASC'
			],
			'limit' => 3
		];

		if ($group !== null) {
			$findConf['conditions']['group'] = $group;
		}

		// generate the task specific conditions.
		foreach ($capabilities as $task) {
			$tmp = [
				'jobtype' => str_replace('queue_', '', $task['name']),
				'AND' => [
					[
						'OR' => [
							'notbefore <' => date('Y-m-d H:i:s'),
							'notbefore' => null
						]
					],
					[
						'OR' => [
							'fetched <' => date('Y-m-d H:i:s', time() - $task['timeout']),
							'fetched' => null
						]
					]
				],
				'failed <' => ($task['retries'] + 1)
			];
			if (array_key_exists('rate', $task) && array_key_exists($tmp['jobtype'], $this->rateHistory)) {
				$tmp['NOW() >='] = date('Y-m-d H:i:s', $this->rateHistory[$tmp['jobtype']] + $task['rate']);
			}
			$findConf['conditions']['OR'][] = $tmp;
		}
		// First, find a list of a few of the oldest unfinished tasks.
		$data = $this->find('all', $findConf);
		if (empty($data)) {
			return [];
		}

		// generate a list of their ID's
		foreach ($data as $item) {
			$idlist[] = $item[$this->name]['id'];
			if (!empty($item[$this->name]['fetched'])) {
				$wasFetched[] = $item[$this->name]['id'];
			}
		}
		// Generate a unique Identifier for the current worker thread
		$key = sha1(microtime());
		// try to update one of the found tasks with the key of this worker.
		$this->query('UPDATE ' . $this->tablePrefix . $this->table . ' SET workerkey = "' . $key . '", fetched = "' . date('Y-m-d H:i:s') . '" WHERE id in(' . implode(',', $idlist) . ') AND (workerkey IS null OR     fetched <= "' . date('Y-m-d H:i:s', time() - $task['timeout']) . '") ORDER BY timediff(NOW(),notbefore) DESC LIMIT 1');
		// read which one actually got updated, which is the job we are supposed to execute.
		$data = $this->find('first', [
			'conditions' => [
				'workerkey' => $key
			]
		]);
		if (empty($data)) {
			return [];
		}
		// if the job had an existing fetched timestamp, increment the failure counter
		if (in_array($data[$this->name]['id'], $wasFetched)) {
			$data[$this->name]['failed']++;
			$data[$this->name]['failure_message'] = 'Restart after timeout';
			$this->save($data);
		}
		//save last fetch by type for Rate Limiting.
		$this->rateHistory[$data[$this->name]['jobtype']] = time();
		return $data[$this->name];
	}

/**
 * Mark a job as Completed, removing it from the queue.
 *
 * @param int $id job ID
 * @return bool Success
 */
	public function markJobDone($id) {
		return $this->updateAll([
			'completed' => "'" . date('Y-m-d H:i:s') . "'"
		], [
			'id' => $id
		]);
	}

/**
 * Mark a job as Failed, Incrementing the failed-counter and Requeueing it.
 *
 * @param int $id job ID
 * @param string $failureMessage Optional message to append to the failure_message field.
 * @return bool Success
 */
	public function markJobFailed($id, $failureMessage = null) {
		$db = $this->getDataSource();
		$fields = [
			'failed' => "failed + 1",
			'failure_message' => $db->value($failureMessage)
		];
		$conditions = [
			'id' => $id
		];
		return $this->updateAll($fields, $conditions);
	}

/**
 * Returns the number of items in the Queue.
 * Either returns the number of ALL pending tasks, or the number of pending tasks of the passed Type
 *
 * @param string $type jobType to Count
 * @return int
 */
	public function getLength($type = null) {
		$findConf = [
			'conditions' => [
				'completed' => null
			]
		];
		if ($type !== null) {
			$findConf['conditions']['jobtype'] = $type;
		}
		return $this->find('count', $findConf);
	}

/**
 * Return a list of all jobtypes in the Queue.
 *
 * @return array
 */
	public function getTypes() {
		$findConf = [
			'fields' => [
				'jobtype'
			],
			'group' => [
				'jobtype'
			]
		];
		return $this->find('list', $findConf);
	}

/**
 * Return some statistics about finished jobs still in the Database.
 *
 * @return array
 */
	public function getStats() {
		$findConf = [
			'fields' => [
				'jobtype,count(id) as num, AVG(UNIX_TIMESTAMP(completed)-UNIX_TIMESTAMP(created)) AS alltime, AVG(UNIX_TIMESTAMP(completed)-UNIX_TIMESTAMP(fetched)) AS runtime, AVG(UNIX_TIMESTAMP(fetched)-IF(notbefore is null,UNIX_TIMESTAMP(created),UNIX_TIMESTAMP(notbefore))) AS fetchdelay'
			],
			'conditions' => [
				'completed NOT' => null
			],
			'group' => [
				'jobtype'
			]
		];
		return $this->find('all', $findConf);
	}

/**
 * Cleanup/Delete Completed Jobs.
 *
 * @return bool Success
 */
	public function cleanOldJobs() {
		return;
		// implement this
		// return $this->deleteAll(array('completed < ' => date('Y-m-d H:i:s', time() - Configure::read('Queue.cleanuptimeout'))));
	}

/**
 * Custom find method, as in `find('progress', ...)`.
 *
 * @param string $state   Current state of find
 * @param array  $query   Search-query
 * @param array  $results Results
 * @return mixed          Based on state
 */
	protected function _findProgress($state, $query = [], $results = []) {
		if ($state === 'before') {

			$query['fields'] = [
				$this->alias . '.reference',
				'(CASE WHEN ' . $this->alias . '.notbefore > NOW() THEN \'NOT_READY\' WHEN ' . $this->alias . '.fetched IS null THEN \'NOT_STARTED\' WHEN ' . $this->alias . '.fetched IS NOT null AND ' . $this->alias . '.completed IS null AND ' . $this->alias . '.failed = 0 THEN \'IN_PROGRESS\' WHEN ' . $this->alias . '.fetched IS NOT null AND ' . $this->alias . '.completed IS null AND ' . $this->alias . '.failed > 0 THEN \'FAILED\' WHEN ' . $this->alias . '.fetched IS NOT null AND ' . $this->alias . '.completed IS NOT null THEN \'COMPLETED\' ELSE \'UNKNOWN\' END) AS status',
				$this->alias . '.failure_message'
			];
			if (isset($query['conditions']['exclude'])) {
				$exclude = $query['conditions']['exclude'];
				unset($query['conditions']['exclude']);
				$exclude = trim($exclude, ',');
				$exclude = explode(',', $exclude);
				$query['conditions'][] = [
					'NOT' => [
						'reference' => $exclude
					]
				];
			}
			if (isset($query['conditions']['group'])) {
				$query['conditions'][][$this->alias . '.group'] = $query['conditions']['group'];
				unset($query['conditions']['group']);
			}
			return $query;
		}
		// state === after
		foreach ($results as $k => $result) {
			$results[$k] = [
				'reference' => $result[$this->alias]['reference'],
				'status' => $result[0]['status']
			];
			if (!empty($result[$this->alias]['failure_message'])) {
				$results[$k]['failure_message'] = $result[$this->alias]['failure_message'];
			}
		}
		return $results;
	}

/**
 * Return jobtypes
 *
 * @param mixed $value value
 * @return array       list of jobtypes
 */
	public static function jobtypes($value = null) {
		$options = [
			self::TYPE_TASK => __d('queue', 'Task'),
			self::TYPE_MODEL => __d('queue', 'Model (Method)'),
		];
		return parent::enum($value, $options);
	}

	const TYPE_TASK = 0;

	const TYPE_MODEL = 1;

}

