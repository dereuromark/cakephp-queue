<?php
namespace Queue\Model\Table;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use Tools\Model\Table\Table;

/**
 * QueuedTask for queued tasks.
 *
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */
class QueuedTasksTable extends Table {

	public $rateHistory = [];

	public $exit = false;

	public $findMethods = [
		'progress' => true
	];

	protected $_key = null;

	public function initialize(array $config) {

	}

	/**
	 * QueuedTask::initConfig()
	 *
	 * @return void
	 */
	public function initConfig() {
		// Local config without extra config file
		$conf = (array)Configure::read('Queue');

		// Fallback to Plugin config which can be overwritten via local app config.
		Configure::load('Queue.queue');
		$defaultConf = (array)Configure::read('Queue');

		// Local app config
		if (file_exists(APP . 'Config' . DS . 'queue.php')) {
			Configure::load('queue');
			$conf += (array)Configure::read('Queue');
		}

		// BC comp:
		$conf = array_merge($defaultConf, $conf, (array)Configure::read('queue'));

		Configure::write('Queue', $conf);
	}

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
		$options = [
			'fields' => function ($query) {
				return [
					'jobtype',
					'num' => $query->func()->count('*'),
					'alltime' => $query->func()->avg('UNIX_TIMESTAMP(completed) - UNIX_TIMESTAMP(created)'),
					'runtime' => $query->func()->avg('UNIX_TIMESTAMP(completed) - UNIX_TIMESTAMP(fetched)'),
					'fetchdelay' => $query->func()->avg('UNIX_TIMESTAMP(fetched) - IF(notbefore is NULL, UNIX_TIMESTAMP(created), UNIX_TIMESTAMP(notbefore))'),
				];
			},
			'conditions' => [
				'completed IS NOT' => null
			],
			'group' => [
				'jobtype'
			]
		];
		return $this->find('all', $options);
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
		$whereClause = [];
		$wasFetched = [];

		$this->virtualFields['age'] = 'IFNULL(TIMESTAMPDIFF(SECOND, NOW(),notbefore), 0)';
		$findCond = [
			'conditions' => [
				'completed IS' => null,
				'OR' => []
			],
			'fields' => [
				'id',
				'jobtype',
				'fetched',
				'age',
			],
			'order' => [
				'age ASC',
				'id ASC'
			],
			'limit' => 3
		];

		if ($group !== null) {
			$findCond['conditions']['group'] = $group;
		}

		// generate the task specific conditions.
		foreach ($capabilities as $task) {
			list($plugin, $name) = pluginSplit($task['name']);
			$tmp = [
				'jobtype' => $name,
				'AND' => [
					[
						'OR' => [
							'notbefore <' => date('Y-m-d H:i:s'),
							'notbefore IS' => null
						]
					],
					[
						'OR' => [
							'fetched <' => date('Y-m-d H:i:s', time() - $task['timeout']),
							'fetched IS' => null
						]
					]
				],
				'failed <' => ($task['retries'] + 1)
			];
			if (array_key_exists('rate', $task) && $tmp['jobtype'] && array_key_exists($tmp['jobtype'], $this->rateHistory)) {
				$tmp['NOW() >='] = date('Y-m-d H:i:s', $this->rateHistory[$tmp['jobtype']] + $task['rate']);
			}
			$findCond['conditions']['OR'][] = $tmp;
		}

		// First, find a list of a few of the oldest unfinished tasks.
		$data = $this->find('all', $findCond);
		if (!$data) {
			return [];
		}

		// Generate a list of already fetched ID's and a where clause for the update statement
		$capTimeout = Hash::combine($capabilities, '{s}.name', '{s}.timeout');
		foreach ($data as $item) {
			$whereClause[] = '(id = ' . $item[$this->alias]['id'] . ' AND (workerkey IS NULL OR fetched <= "' . date('Y-m-d H:i:s', time() - $capTimeout[$item[$this->alias]['jobtype']]) . '"))';
			if (!empty($item[$this->alias]['fetched'])) {
				$wasFetched[] = $item[$this->alias]['id'];
			}
		}

		$key = $this->key();
		//debug($key);ob_flush();

		// try to update one of the found tasks with the key of this worker.
		$this->query('UPDATE ' . $this->tablePrefix . $this->table . ' SET workerkey = "' . $key . '", fetched = "' . date('Y-m-d H:i:s') . '" WHERE ' . implode(' OR ', $whereClause) . ' ORDER BY ' . $this->virtualFields['age'] . ' ASC, id ASC LIMIT 1');

		// Read which one actually got updated, which is the job we are supposed to execute.
		$data = $this->find('first', [
			'conditions' => [
				'workerkey' => $key,
				'completed' => null,
			],
			'order' => ['fetched' => 'DESC']
		]);
		if (empty($data)) {
			return [];
		}

		// If the job had an existing fetched timestamp, increment the failure counter
		if (in_array($data[$this->alias]['id'], $wasFetched)) {
			$data[$this->alias]['failed']++;
			$data[$this->alias]['failure_message'] = 'Restart after timeout';
			$this->id = $data[$this->alias]['id'];
			$this->save($data, false, ['id', 'failed', 'failure_message']);
		}
		//save last fetch by type for Rate Limiting.
		$this->rateHistory[$data[$this->alias]['jobtype']] = time();
		return $data[$this->alias];
	}

/**
 * QueuedTask::updateProgress()
 *
 * @param int $id ID of task
 * @param float $progress Value from 0 to 1
 * @return bool Success
 */
	public function updateProgress($id, $progress) {
		if (!$id) {
			return false;
		}
		$this->id = $id;
		return (bool)$this->saveField('progress', round($progress, 2));
	}

/**
 * Mark a job as Completed, removing it from the queue.
 *
 * @param int $id ID of task
 * @return bool Success
 */
	public function markJobDone($id) {
		$fields = [
			$this->alias . '.completed' => "'" . date('Y-m-d H:i:s') . "'"
		];
		$conditions = [
			$this->alias . '.id' => $id
		];
		return $this->updateAll($fields, $conditions);
	}

/**
 * Mark a job as Failed, Incrementing the failed-counter and Requeueing it.
 *
 * @param int $id ID of task
 * @param string $failureMessage Optional message to append to the failure_message field.
 * @return bool Success
 */
	public function markJobFailed($id, $failureMessage = null) {
		$db = $this->getDataSource();
		$fields = [
			$this->alias . '.failed' => $this->alias . '.failed + 1',
			$this->alias . '.failure_message' => $db->value($failureMessage),
		];
		$conditions = [
			$this->alias . '.id' => $id
		];
		return $this->updateAll($fields, $conditions);
	}

/**
 * Return some statistics about unfinished jobs still in the Database.
 *
 * @return array
 */
	public function getPendingStats() {
		$findCond = [
			'fields' => [
				'jobtype',
				'created',
				'status',
				'fetched',
				'progress',
				'reference',
				'failed',
				'failure_message'
			],
			'conditions' => [
				'completed' => null
			]
		];
		return $this->find('all', $findCond);
	}

/**
 * Cleanup/Delete Completed Jobs.
 *
 * @return void
 */
	public function cleanOldJobs() {
		$this->deleteAll([
			'completed <' => date('Y-m-d H:i:s', time() - Configure::read('Queue.cleanuptimeout'))
		]);
		if (!($pidFilePath = Configure::read('Queue.pidfilepath'))) {
			return;
		}
		// Remove all old pid files left over
		$timeout = time() - 2 * Configure::read('Queue.cleanuptimeout');
		$Iterator = new \RegexIterator(
			new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pidFilePath)),
			'/^.+\_.+\.(pid)$/i',
			\RegexIterator::MATCH
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

/**
 * QueuedTask::lastRun()
 *
 * @deprecated?
 * @return array
 */
	public function lastRun() {
		$workerFileLog = LOGS . 'queue' . DS . 'runworker.txt';
		if (file_exists($workerFileLog)) {
			$worker = file_get_contents($workerFileLog);
		}
		return [
			'worker' => isset($worker) ? $worker : '',
			'queue' => $this->field('completed', ['completed !=' => null], ['completed' => 'DESC']),
		];
	}

/**
 * QueuedTask::_findProgress()
 *
 * Custom find method, as in `find('progress', ...)`.
 *
 * @param string $state Current state
 * @param array $query Parameters
 * @param array $results Results
 * @return array         Query/Results based on state
 */
	protected function _findProgress($state, $query = [], $results = []) {
		if ($state === 'before') {
			$query['fields'] = [
				$this->alias . '.reference',
				$this->alias . '.status',
				$this->alias . '.progress',
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
				'status' => $result[$this->alias]['status']
			];
			if (!empty($result[$this->alias]['progress'])) {
				$results[$k]['progress'] = $result[$this->alias]['progress'];
			}
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
		$numX = count($x);
		while ($start <= $numX) {
			$this->deleteAll([
				'id' => array_slice($x, $start, 10)
			]);
			debug(array_slice($x, $start, 10));
			$start = $start + 100;
		}
	}

/**
 * Generate a unique Identifier for the current worker thread.
 *
 * Useful to idendify the currently running processes for this thread.
 *
 * @return string Identifier
 */
	public function key() {
		if ($this->_key !== null) {
			return $this->_key;
		}
		$this->_key = sha1(microtime());
		return $this->_key;
	}

/**
 * Truncate table.
 *
 * @return array Query results
 */
	public function truncate($table = null) {
		if ($table === null) {
			$table = $this->table();
		}
		return $this->query('TRUNCATE TABLE `' . $table . '`');
	}

/**
 * Cleanup (remove the identifier from the db records?)
 *
 * TO-DO: FIXME
 *
 * @return void
 */
	/*
	public function __destruct() {
		$this->query('UPDATE ' . $this->tablePrefix . $this->table . ' SET workerkey = "" WHERE workerkey = "' . $this->_key() . '" LIMIT 1');

		parent::__destruct();
	}
	*/

}
