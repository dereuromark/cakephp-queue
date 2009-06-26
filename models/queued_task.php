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

	/**
	 * Add a new Job to the Queue
	 *
	 * @param string $jobName QueueTask name
	 * @param array $data any array
	 * @return bool success
	 */
	public function createJob($jobName, $data, $notBefore = null) {

		$data = array(
			'jobtype' => $jobName,
			'data' => serialize($data)
		);
		if ($notBefore != null) {
			$data['notbefore'] = date('Y-m-d H:i:s', strtotime($notBefore));
		}
		return ($this->save($this->create($data)));
	}

	/**
	 * Look for a new job that can be processed with the current abilities.
	 *
	 * @param array $capabilities Available QueueWorkerTasks.
	 * @return Array Taskdata.
	 */
	public function requestJob($capabilities) {

		$findConf = array(
			'conditions' => array(
				'completed' => null,
				'OR' => array()
			)
		);
		// generate the task specific conditions.
		foreach ($capabilities as $task) {
			$findConf['conditions']['OR'][] = array(
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
		}

		$data = $this->find('first', $findConf);
		if (is_array($data)) {
			$this->id = $data[$this->name]['id'];
			$this->saveField('fetched', date('Y-m-d H:i:s'));
			$this->id = null;
			return $data[$this->name];
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
	 */
	public function markJobFailed($id) {
		$findConf = array(
			'conditions' => array(
				'id' => $id
			)
		);
		$data = $this->find('first', $findConf);
		if (is_array($data)) {
			$data[$this->name]['failed']++;
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
	 * Cleanup/Delete Completed Jobs.
	 *
	 */
	public function cleanOldJobs() {
		$this->deleteAll(array(
			'completed < ' => date('Y-m-d H:i:s', time() - 2000)
		));

	}

}
?>