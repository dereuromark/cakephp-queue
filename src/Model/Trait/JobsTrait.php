<?php

namespace Queue\Model\Traits;

trait JobsTrait {

	/**
	 * Add a new Job to the Queue.
	 *
	 * @param string $jobName   QueueTask name
	 * @param array  $data      any array
	 * @param array  $notBefore optional date which must not be preceded
	 * @param string $group     Used to group similar QueuedTasks.
	 * @param string $reference An optional reference string.
	 * @return array            Created Job array containing id, data, ...
	 */
	public function createJob($jobName, $data = null, $notBefore = null, $group = null, $reference = null)
	{
		$data = [
			'jobtype' => $jobName,
			'data' => serialize($data),
			'group' => $group,
			'reference' => $reference
		];
		if ($notBefore !== null) {
			$data['notbefore'] = date('Y-m-d H:i:s', strtotime($notBefore));
		}
		$this->create();
		return $this->save($data);
	}

	/**
	 * Set exit to true on error
	 *
	 * @return void
	 */
	public function onError()
	{
		$this->exit = true;
	}

	/**
	 * Returns the number of items in the Queue.
	 * Either returns the number of ALL pending tasks, or the number of pending tasks of the passed Type
	 *
	 * @param string $type jobType to Count
	 * @return int
	 */
	public function getLength($type = null)
	{
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
		$findCond = [
			'fields' => [
				'jobtype'
			],
			'group' => [
				'jobtype'
			]
		];
		return $this->find('list', $findCond);
	}

	/**
	 * Return some statistics about finished jobs still in the Database.
	 * TO-DO: rewrite as virtual field
	 *
	 * @return array
	 */
	public function getStats() {
		$findCond = [
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
		return $this->find('all', $findCond);
	}

}
