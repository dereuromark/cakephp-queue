<?php
App::uses('QueueAppModel', 'Queue.Model');
App::uses('Sanitize', 'Utility');

/**
 * @author MGriesbach@gmail.com
 * @package QueuePlugin
 * @subpackage QueuePlugin.Models
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */
class QueuedTask extends QueueAppModel {


	public $rateHistory = array();

	public $exit = false;

	public $_findMethods = array(
		'progress' => true
	);

	protected $_key = null;

	/**
	 * Add a new Job to the Queue
	 *
	 * @param string $jobName QueueTask name
	 * @param array $data any array
	 * @param string $group Used to group similar QueuedTasks
	 * @param string $reference any array
	 * @return bool success
	 */
	public function createJob($jobName, $data, $notBefore = null, $group = null, $reference = null) {

		$data = array(
			'jobtype' => $jobName,
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
	 * @return Array Taskdata.
	 */
	public function requestJob($capabilities, $group = null) {
		$idlist = array();
		$wasFetched = array();

		$findCond = array(
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
				'age ASC',
				'id ASC'
			),
			'limit' => 3
		);

		if (!is_null($group)) {
			$findCond['conditions']['group'] = $group;
		}

		// generate the task specific conditions.
		foreach ($capabilities as $task) {
			list ($plugin, $name) = pluginSplit($task['name']);
			$tmp = array(
				'jobtype' => substr($name, 5),
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
			$findCond['conditions']['OR'][] = $tmp;
		}

		// First, find a list of a few of the oldest unfinished tasks.
		$data = $this->find('all', $findCond);

		if (is_array($data) && count($data) > 0) {
			// generate a list of their ID's
			foreach ($data as $item) {
				$idlist[] = $item[$this->alias]['id'];
				if (!empty($item[$this->alias]['fetched'])) {
					$wasFetched[] = $item[$this->alias]['id'];
				}
			}

			$key = $this->key();
			// try to update one of the found tasks with the key of this worker.
			$this->query('UPDATE ' . $this->tablePrefix . $this->table . ' SET workerkey = "' . $key . '", fetched = "' . date('Y-m-d H:i:s') . '" WHERE id in(' . implode(',', $idlist) . ') AND (workerkey IS NULL OR     fetched <= "' . date('Y-m-d H:i:s', time() - $task['timeout']) . '") ORDER BY timediff(NOW(),notbefore) ASC, id ASC LIMIT 1');
			// read which one actually got updated, which is the job we are supposed to execute.
			$data = $this->find('first', array(
				'conditions' => array(
					'workerkey' => $key,
					'completed' => null,
				)
			));
			if (is_array($data)) {
				// if the job had an existing fetched timestamp, increment the failure counter
				if (in_array($data[$this->alias]['id'], $wasFetched)) {
					$data[$this->alias]['failed']++;
					$data[$this->alias]['failure_message'] = 'Restart after timeout';
					$this->id = $data[$this->alias]['id'];
					$this->save($data);
				}
				//save last fetch by type for Rate Limiting.
				$this->rateHistory[$data[$this->alias]['jobtype']] = time();
				return $data[$this->alias];
			}
		}
		return false;
	}

	/**
	 * Mark a job as Completed, removing it from the queue.
	 *
	 * @param integer $id
	 * @return bool Success
	 */
	public function markJobDone($id) {
		$fields = array(
			'completed' => "'" . date('Y-m-d H:i:s') . "'"
		);
		$conditions = array(
			'id' => $id
		);
		return $this->updateAll($fields, $conditions);
	}

	/**
	 * Mark a job as Failed, Incrementing the failed-counter and Requeueing it.
	 *
	 * @param integer $id
	 * @param string $failureMessage Optional message to append to the
	 * failure_message field
	 */
	public function markJobFailed($id, $failureMessage = null) {
		$fields = array(
			'failed' => "failed + 1",
			'failure_message' => "'" . Sanitize::escape($failureMessage) . "'"
		);
		$conditions = array(
			'id' => $id
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
		$findCond = array(
			'conditions' => array(
				'completed' => null
			)
		);
		if ($type != NULL) {
			$findCond['conditions']['jobtype'] = $type;
		}
		return $this->find('count', $findCond);
	}

	/**
	 * Return a list of all jobtypes in the Queue.
	 *
	 * @return array
	 */
	public function getTypes() {
		$findCond = array(
			'fields' => array(
				'jobtype'
			),
			'group' => array(
				'jobtype'
			)
		);
		return $this->find('list', $findCond);
	}

	/**
	 * Return some statistics about finished jobs still in the Database.
	 * @return array
	 */
	public function getStats() {
		$findCond = array(
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
		return $this->find('all', $findCond);
	}

	/**
	 * Cleanup/Delete Completed Jobs.
	 *
	 */
	public function cleanOldJobs() {
		$this->deleteAll(array(
			'completed < ' => date('Y-m-d H:i:s', time() - Configure::read('queue.cleanuptimeout'))
		));
		if ($pidFilePath = Configure::read('queue.pidfilepath')) {
			# remove all old pid files left over
			$timeout = time() - 2 * Configure::read('queue.cleanuptimeout');
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
	}

	public function lastRun() {
		$workerFileLog = LOGS.'queue'.DS.'runworker.txt';
		if (file_exists($workerFileLog)) {
			$worker = file_get_contents($workerFileLog);
		}
		return array(
			'worker' => isset($worker) ? $worker : '',
			'queue' => $this->field('completed', array('completed !='=>null), array('completed'=>'DESC')),
		);
	}


	protected function _findProgress($state, $query = array(), $results = array()) {
		if ($state == 'before') {

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
		} else {
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
	}

	public function clearDoublettes() {
		$x = $this->query('SELECT max(id) as id FROM `queueman`.`queued_tasks`
    where completed is null
    group by data
    having count(id) > 1');

		$start = 0;
		$x = array_keys($x);
		while ($start <= count($x)) {
			debug($this->deleteAll(array(
				'id' => array_slice($x, $start, 10)
			)));
			debug(array_slice($x, $start, 10));
			$start = $start + 100;
		}
	}

	/**
	 * Generate a unique Identifier for the current worker thread
	 *
	 * useful to idendify the currently running processes for this thread
	 * @return string $identifier
	 */
	public function key() {
		if ($this->_key !== null) {
			return $this->_key;
		}
		$this->_key = sha1(microtime());
		return $this->_key;
	}

	/**
	 * Cleanup (remove the identifier from the db records?)
	 *
	 */
	/*
	public function __destruct() {
		$this->query('UPDATE ' . $this->tablePrefix . $this->table . ' SET workerkey = "" WHERE workerkey = "' . $this->_key() . '" LIMIT 1');

		parent::__destruct();
	}
	*/

}
