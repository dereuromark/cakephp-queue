<?php

/**
 * @author MGriesbach@gmail.com
 * @package QueuePlugin
 * @subpackage QueuePlugin.Models
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
		foreach ($capabilities as &$cp) {
			$cp = str_replace('queue_', '', $cp);
		}
		$findConf = array(
			'conditions' => array(
				'jobtype' => $capabilities,
				'completed' => null,
				'OR' => array(
					'fetched' => null,
					'fetched <' => date('Y-m-d H:i:s', time() - 20)
				),
				'failed < ' => 4
			)
		);
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
		$this->id = $id;
		$return = $this->saveField('completed', date('Y-m-d H:i:s'));
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