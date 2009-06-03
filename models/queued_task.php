<?php

/**
 * @author MGriesbach@gmail.com
 * @package QueuePlugin
 * @subpackage QueuePlugin.Models
 */
class QueuedTask extends AppModel {

	public $name = 'QueuedTask';

	public function createJob($jobName, $data) {
		return ($this->save($this->create(array(
			'jobtype' => $jobName,
			'data' => serialize($data)
		))));
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
				'jobtype' => $capabilities,
				'completed' => null,
				'OR' => array(
					'fetched' => null,
					'fetched <' => date('Y-m-d H:m:s', time() - 20)
				),
				'failed <= ' => 3
			)
		);
		$data = $this->find('first', $findConf);
		if (is_array($data)) {
			$this->id = $data[$this->name]['id'];
			$this->saveField('fetched', date('Y-m-d H:m:s'));
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
		$this->id = $id;
		$return = $this->saveField('completed', date('Y-m-d H:m:s'));
		$this->id = null;
		return ($return);
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
		}
		$this->save($data);
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
			'completed < ' => date('Y-m-d H:m:s', time() - 2000)
		));

	}

}
?>