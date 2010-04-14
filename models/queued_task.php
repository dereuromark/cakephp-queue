<?php

/**
 * @author MGriesbach@gmail.com
 * @package QueuePlugin
 * @subpackage QueuePlugin.Models
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */
class QueuedTask extends AppModel {

	public $name = 'QueuedTask';

	public $rateHistory = array();

	/**
	 * Add a new Job to the Queue
	 *
	 * @param string $jobName QueueTask name
	 * @param array $data any array
	 * @param string $group Used to group similar QueuedTasks
	 * @param string $reference any array
	 * @return bool success
	 */
	public function createJob($jobName, $data, $notBefore = null, $group = null) {

		$data = array(
			'jobtype' => $jobName,
			'data' => serialize($data),
			'group' => $group,
		);
		if ($notBefore != null) {
			$data['notbefore'] = date('Y-m-d H:i:s', strtotime($notBefore));
		}
		return ($this->save($this->create($data)));
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

    if (!is_null($group)) {
      $findConf['conditions']['group'] = $group;
    }

		// generate the task specific conditions.
		foreach ($capabilities as $task) {
			$tmp = array(
				'jobtype' => str_replace('queue_', '', $task['name']),
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
		if (is_array($data) && count($data) > 0) {
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
			$this->query('UPDATE ' . $this->tablePrefix . $this->table . ' SET workerkey = "' . $key . '", fetched = "' . date('Y-m-d H:i:s') . '" WHERE id in(' . implode(',', $idlist) . ') AND (workerkey IS NULL OR     fetched <= "' . date('Y-m-d H:i:s', time() - $task['timeout']) . '") ORDER BY timediff(NOW(),notbefore) DESC LIMIT 1');
			// read which one actually got updated, which is the job we are supposed to execute.
			$data = $this->find('first', array(
				'conditions' => array(
					'workerkey' => $key
				)
			));
			if (is_array($data)) {
				// if the job had an existing fetched timestamp, increment the failure counter
				if (in_array($data[$this->name]['id'], $wasFetched)) {
					$data[$this->name]['failed']++;
					$this->saveField('failed', $data[$this->name]['failed']);
				}
				//save last fetch by type for Rate Limiting.
				$this->rateHistory[$data[$this->name]['jobtype']] = time();
				return $data[$this->name];
			}
		}
		return FALSE;
	}

	/**
	 * Mark a job as Completed, removing it from the queue.
	 *
	 * @param integer $id
	 * @return bool Success
	 */
	public function markJobDone($id) {
		return ($this->updateAll(array(
			'completed' => "'" . date('Y-m-d H:i:s') . "'"
		), array(
			'id' => $id
		)));
	}

	/**
	 * Mark a job as Failed, Incrementing the failed-counter and Requeueing it.
	 *
	 * @param integer $id
   * @param string $failureMessage Optional message to append to the
   *  failure_message field
	 */
	public function markJobFailed($id, $failureMessage = null) {
		$findConf = array(
			'conditions' => array(
				'id' => $id
			)
		);
		$data = $this->find('first', $findConf);
		if (is_array($data)) {
			$data[$this->name]['failed']++;
      $data[$this->name]['failure_message'] .= date('r') . "\n" . $failureMessage . "\n";
			return (is_array($this->save($data)));
		}
		return false;
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
		if ($type != NULL) {
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
	 */
	public function cleanOldJobs() {
		$this->deleteAll(array(
			'completed < ' => date('Y-m-d H:i:s', time() - Configure::read('queue.cleanuptimeout'))
		));

	}

}
?>