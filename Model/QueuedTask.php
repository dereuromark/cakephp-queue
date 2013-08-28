<?php
App::uses('QueueAppModel', 'Queue.Model');

/**
 * QueuedTask for queued tasks.
 *
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */
class QueuedTask extends QueueAppModel {

	public $rateHistory = array();

	public $exit = false;

	public $findMethods = array(
		'progress' => true
	);

	/**
	 * Add a new Job to the Queue.
	 *
	 * @param string $jobName QueueTask name
	 * @param array $data any array
	 * @param string $group Used to group similar QueuedTasks
	 * @param string $reference any array
	 * @return boolean Success.
	 */
	public function createJob($jobName, $data, $notBefore = null, $group = null, $reference = null) {
		$data = array(
			'jobtype' => ucfirst($jobName),
			'data' => serialize($data),
			'group' => $group,
			'reference' => $reference
		);
		if ($notBefore != null) {
			$data['notbefore'] = date('Y-m-d H:i:s', strtotime($notBefore));
		}
		$this->create();
		return $this->save($data);
	}

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
		$idlist = array();
		$wasFetched = array();

		$findConf = array(
			'conditions' => array(
				'completed' => null,
				'OR' => array()
			),
			'fields' => array(
				'id',
				'fetched',
				'timediff(NOW(),notbefore) AS age'
			),
			'order' => array(
				'age DESC',
				'id ASC'
			),
			'limit' => 3
		);

		if ($group !== null) {
			$findConf['conditions']['group'] = $group;
		}

		// generate the task specific conditions.
		foreach ($capabilities as $task) {
			$tmp = array(
				'jobtype' => ucfirst(str_replace('Queue', '', $task['name'])),
				'AND' => array(
					array(
						'OR' => array(
							'notbefore <' => date('Y-m-d H:i:s'),
							'notbefore' => null
						)
					),
					array(
						'OR' => array(
							'fetched <' => date('Y-m-d H:i:s', time() - $task['timeout']),
							'fetched' => null
						)
					)
				),
				'failed <' => ($task['retries'] + 1)
			);
			if (array_key_exists('rate', $task) && array_key_exists($tmp['jobtype'], $this->rateHistory)) {
				$tmp['NOW() >='] = date('Y-m-d H:i:s', $this->rateHistory[$tmp['jobtype']] + $task['rate']);
			}
			$findConf['conditions']['OR'][] = $tmp;
		}

		// First, find a list of a few of the oldest unfinished tasks.
		$data = $this->find('all', $findConf);
		if (empty($data)) {
			return array();
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
		$this->query('UPDATE ' . $this->tablePrefix . $this->table . ' SET workerkey = "' . $key . '", fetched = "' . date('Y-m-d H:i:s') .
			'" WHERE id in(' . implode(',', $idlist) . ') AND (workerkey IS NULL OR fetched <= "' . date('Y-m-d H:i:s', time() - $task['timeout']) . '") ORDER BY timediff(NOW(),notbefore) DESC LIMIT 1');
		// read which one actually got updated, which is the job we are supposed to execute.
		$data = $this->find('first', array(
			'conditions' => array(
				$this->alias . '.workerkey' => $key
			)
		));
		if (empty($data)) {
			return array();
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
	 * @param integer $id
	 * @return boolean Success
	 */
	public function markJobDone($id) {
		$fields = array(
			$this->alias . '.completed' => "'" . date('Y-m-d H:i:s') . "'"
		);
		$conditions = array(
			$this->alias . '.id' => $id
		);
		return $this->updateAll($fields, $conditions);
	}

	/**
	 * Mark a job as Failed, Incrementing the failed-counter and Requeueing it.
	 *
	 * @param integer $id
	 * @param string $failureMessage Optional message to append to the failure_message field.
	 */
	public function markJobFailed($id, $failureMessage = null) {
		$fields = array(
			$this->alias . '.failed' => $this->alias . '.failed + 1',
			$this->alias . '.failure_message' => $failureMessage,
			//$this->alias . '.workerkey' => null
		);
		$conditions = array(
			$this->alias . '.id' => $id
		);
		return $this->updateAll($fields, $conditions);
	}

	/**
	 * Returns the number of items in the Queue.
	 * Either returns the number of ALL pending tasks, or the number of pending tasks of the passed Type
	 *
	 * @param string $type jobType to Count
	 * @return integer
	 */
	public function getLength($type = null) {
		$findConf = array(
			'conditions' => array(
				'completed' => null
			)
		);
		if ($type) {
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
		$findConf = array(
			'fields' => array(
				'jobtype'
			),
			'group' => array(
				'jobtype'
			)
		);
		return $this->find('list', $findConf);
	}

	/**
	 * Return some statistics about finished jobs still in the Database.
	 * //TODO: rewrite as virtual field
	 *
	 * @return array
	 */
	public function getStats() {
		$findConf = array(
			'fields' => array(
				'jobtype,count(id) as num, AVG(UNIX_TIMESTAMP(completed)-UNIX_TIMESTAMP(created)) AS alltime, AVG(UNIX_TIMESTAMP(completed)-UNIX_TIMESTAMP(fetched)) AS runtime, AVG(UNIX_TIMESTAMP(fetched)-IF(notbefore is null,UNIX_TIMESTAMP(created),UNIX_TIMESTAMP(notbefore))) AS fetchdelay'
			),
			'conditions' => array(
				'completed NOT' => null
			),
			'group' => array(
				'jobtype'
			)
		);
		return $this->find('all', $findConf);
	}

	/**
	 * Cleanup/Delete Completed Jobs.
	 *
	 * @return boolean True on success, false on failure
	 */
	public function cleanOldJobs() {
		$conditions = array(
			$this->alias . '.completed <' => date('Y-m-d H:i:s', time() - Configure::read('Queue.cleanuptimeout'))
		);
		return $this->deleteAll($conditions);
	}

	/**
	 * QueuedTask::lastRun()
	 *
	 * @return array
	 */
	public function lastRun() {
		$workerFileLog = LOGS . 'queue' . DS . 'runworker.txt';
		if (file_exists($workerFileLog)) {
			$worker = file_get_contents($workerFileLog);
		}
		return array(
			'worker' => isset($worker) ? $worker : '',
			'queue' => $this->field('completed', array('completed !=' => null), array('completed' => 'DESC')),
		);
	}

	/**
	 * QueuedTask::_findProgress()
	 *
	 * @param string $state
	 * @param array $query
	 * @param array $results
	 * @return array
	 */
	protected function _findProgress($state, $query = array(), $results = array()) {
		if ($state === 'before') {
			$query['fields'] = array(
				$this->alias . '.reference',
				'(CASE WHEN ' . $this->alias . '.notbefore > NOW() THEN \'NOT_READY\' WHEN ' . $this->alias . '.fetched IS NULL THEN \'NOT_STARTED\' WHEN ' . $this->alias . '.fetched IS NOT NULL AND ' . $this->alias . '.completed IS NULL AND ' . $this->alias . '.failed = 0 THEN \'IN_PROGRESS\' WHEN ' . $this->alias . '.fetched IS NOT NULL AND ' . $this->alias . '.completed IS NULL AND ' . $this->alias . '.failed > 0 THEN \'FAILED\' WHEN ' . $this->alias . '.fetched IS NOT NULL AND ' . $this->alias . '.completed IS NOT NULL THEN \'COMPLETED\' ELSE \'UNKNOWN\' END) AS status',
				$this->alias . '.failure_message'
			);
			if (isset($query['conditions']['exclude'])) {
				$exclude = $query['conditions']['exclude'];
				unset($query['conditions']['exclude']);
				$exclude = trim($exclude, ',');
				$exclude = explode(',', $exclude);
				$query['conditions'][] = array(
					'NOT' => array(
						'reference' => $exclude
					)
				);
			}
			if (isset($query['conditions']['group'])) {
				$query['conditions'][][$this->alias . '.group'] = $query['conditions']['group'];
				unset($query['conditions']['group']);
			}
			return $query;
		}
		// state === after
		foreach ($results as $k => $result) {
			$results[$k] = array(
				'reference' => $result[$this->alias]['reference'],
				'status' => $result[0]['status']
			);
			if (!empty($result[$this->alias]['failure_message'])) {
				$results[$k]['failure_message'] = $result[$this->alias]['failure_message'];
			}
		}
		return $results;
	}

	/**
	 * QueuedTask::clearDoublettes()
	 * //FIXME
	 *
	 * @return void
	 */
	public function clearDoublettes() {
		$x = $this->query('SELECT max(id) as id FROM `' . $this->tablePrefix . $this->table . '`
	WHERE completed is null
	GROUP BY data
	HAVING COUNT(id) > 1');

		$start = 0;
		$x = array_keys($x);
		while ($start <= count($x)) {
			$this->deleteAll(array(
				'id' => array_slice($x, $start, 10)
			));
			debug(array_slice($x, $start, 10));
			$start = $start + 100;
		}
	}

}
