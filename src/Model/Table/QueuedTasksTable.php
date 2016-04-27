<?php

namespace Queue\Model\Table;

use Cake\Core\Configure;
use Cake\I18n\Time;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * QueuedTask for queued tasks.
 *
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */
class QueuedTasksTable extends Table {

	/**
	 * @var array
	 */
	public $rateHistory = [];

	/**
	 * @var bool
	 */
	public $exit = false;

	/**
	 * @var array
	 */
	public $findMethods = [
		'progress' => true,
	];

	/**
	 * @var string|null
	 */
	protected $_key = null;

	/**
	 * initialize Table
	 *
	 * @param array $config Configuration
	 * @return void
	 */
	public function initialize(array $config) {
		parent::initialize($config);

		$this->addBehavior('Timestamp');

		$this->initConfig();
	}

	/**
	 * @return void
	 */
	public function initConfig() {
		// Local config without extra config file
		$conf = (array)Configure::read('Queue');

		// Fallback to Plugin config which can be overwritten via local app config.
		Configure::load('Queue.queue');
		$defaultConf = (array)Configure::read('Queue');

		$conf = array_merge($defaultConf, $conf);

		Configure::write('Queue', $conf);
	}

	/**
	 * Add a new Job to the Queue.
	 *
	 * @param string $jobName   QueueTask name
	 * @param array|null $data      any array
	 * @param array|null $notBefore optional date which must not be preceded
	 * @param string|null $group     Used to group similar QueuedTasks.
	 * @param string|null $reference An optional reference string.
	 * @return \Cake\ORM\Entity Saved job entity
	 * @throws \Exception
	 */
	public function createJob($jobName, $data = null, $notBefore = null, $group = null, $reference = null) {
		$data = [
			'jobtype' => $jobName,
			'data' => serialize($data),
			'task_group' => $group,
			'reference' => $reference,
		];
		if ($notBefore !== null) {
			$data['notbefore'] = new Time($notBefore);
		}
		$queuedTask = $this->newEntity($data);
		if ($queuedTask->errors()) {
			throw new Exception('Invalid entity data');
		}
		return $this->save($queuedTask);
	}

	/**
	 * Set exit to true on error
	 *
	 * @return void
	 */
	public function onError() {
		$this->exit = true;
	}

	/**
	 * Returns the number of items in the Queue.
	 * Either returns the number of ALL pending tasks, or the number of pending tasks of the passed Type
	 *
	 * @param string|null $type jobType to Count
	 * @return int
	 */
	public function getLength($type = null) {
		$findConf = [
			'conditions' => [
				'completed IS' => null,
			],
		];
		if ($type !== null) {
			$findConf['conditions']['jobtype'] = $type;
		}
		$data = $this->find('all', $findConf);
		return $data->count();
	}

	/**
	 * Return a list of all jobtypes in the Queue.
	 *
	 * @return array
	 */
	public function getTypes() {
		$findCond = [
			'fields' => [
				'jobtype',
			],
			'group' => [
				'jobtype',
			],
			'keyField' => 'jobtype',
			'valueField' => 'jobtype',
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
				'completed IS NOT' => null,
			],
			'group' => [
				'jobtype',
			],
		];
		return $this->find('all', $options);
	}

	/**
	 * Look for a new job that can be processed with the current abilities and
	 * from the specified group (or any if null).
	 *
	 * @param array $capabilities Available QueueWorkerTasks.
	 * @param string|null $group Request a job from this group, (from any group if null)
	 * @return array Taskdata.
	 */
	public function requestJob(array $capabilities, $group = null) {
		$whereClause = [];
		$wasFetched = [];

		//$this->virtualFields['age'] = 'IFNULL(TIMESTAMPDIFF(SECOND, NOW(),notbefore), 0)';
		$findCond = [
			'conditions' => [
				'completed IS' => null,
				'OR' => [],
			],
			'fields' => function ($query) {
				return [
					'id',
					'jobtype',
					'fetched',
					//'age' => $query->func('IFNULL(TIMESTAMPDIFF(SECOND, NOW(), notbefore), 0)'),
					'age' => $query->newExpr()->add('IFNULL(TIMESTAMPDIFF(SECOND, NOW(), notbefore), 0)'),
				];
			},
			'order' => [
				'age ASC',
				'id ASC',
			],
			'limit' => 3,
		];

		if ($group !== null) {
			$findCond['conditions']['task_group'] = $group;
		}

		// generate the task specific conditions.
		foreach ($capabilities as $task) {
			list($plugin, $name) = pluginSplit($task['name']);
			$tmp = [
				'jobtype' => $name,
				'AND' => [
					[
						'OR' => [
							'notbefore <' => new Time(),
							'notbefore IS' => null,
						],
					],
					[
						'OR' => [
							'fetched <' => (new Time())->modify(sprintf('-%d seconds', $task['timeout'])),
							'fetched IS' => null,
						],
					],
				],
				'failed <' => ($task['retries'] + 1),
			];
			if (array_key_exists('rate', $task) && $tmp['jobtype'] && array_key_exists($tmp['jobtype'], $this->rateHistory)) {
				$tmp['UNIX_TIMESTAMP() >='] = $this->rateHistory[$tmp['jobtype']] + $task['rate'];
			}
			$findCond['conditions']['OR'][] = $tmp;
		}

		// First, find a list of a few of the oldest unfinished tasks.
		$data = $this->find('all', $findCond)->all()->toArray();
		if (!$data) {
			return null;
		}

		// Generate a list of already fetched ID's and a where clause for the update statement
		$capTimeout = Hash::combine($capabilities, '{s}.name', '{s}.timeout');
		foreach ($data as $item) {
			$whereClause[] = '(id = ' . $item['id'] . ' AND (workerkey IS NULL OR fetched <= "' . date('Y-m-d H:i:s', time() - $capTimeout[$item['jobtype']]) . '"))';
			if (!empty($item['fetched'])) {
				$wasFetched[] = $item['id'];
			}
		}

		$key = $this->key();
		//debug($key);ob_flush();

		// try to update one of the found tasks with the key of this worker.
		$virtualFields['age'] = 'IFNULL(TIMESTAMPDIFF(SECOND, NOW(),notbefore), 0)';
		$this->_connection->query('UPDATE ' . $this->table() . ' SET workerkey = "' . $key . '", fetched = "' . date('Y-m-d H:i:s') . '" WHERE ' . implode(' OR ', $whereClause) . ' ORDER BY ' . $virtualFields['age'] . ' ASC, id ASC LIMIT 1');

		// Read which one actually got updated, which is the job we are supposed to execute.
		$data = $this->find('all', [
			'conditions' => [
				'workerkey' => $key,
				'completed IS' => null,
			],
			'order' => ['fetched' => 'DESC'],
		])->first();

		if (!$data) {
			return null;
		}

		// If the job had an existing fetched timestamp, increment the failure counter
		if (in_array($data['id'], $wasFetched)) {
			$data['failed']++;
			$data['failure_message'] = 'Restart after timeout';
			//$this->id = $data['id'];
			$this->save($data, ['fieldList' => ['id', 'failed', 'failure_message']]);
		}
		//save last fetch by type for Rate Limiting.
		$this->rateHistory[$data['jobtype']] = (new Time())->toUnixString();
		return $data;
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
		return (bool)$this->updateAll(['progress' => round($progress, 2)], ['id' => $id]);
	}

	/**
	 * Mark a job as Completed, removing it from the queue.
	 *
	 * @param int $id ID of task
	 * @return bool Success
	 */
	public function markJobDone($id) {
		$fields = [
			'completed' => date('Y-m-d H:i:s'),
		];
		$conditions = [
			'id' => $id,
		];
		return $this->updateAll($fields, $conditions);
	}

	/**
	 * Reset current jobs
	 *
	 * @return bool Success
	 */
	public function reset() {
		$fields = [
			'completed' => null,
			'fetched' => null,
			'progress' => 0,
			'failed' => 0,
			'workerkey' => null,
			'failure_message' => null,
		];
		$conditions = [
			'completed IS' => null,
		];
		return $this->updateAll($fields, $conditions);
	}

	/**
	 * Mark a job as Failed, Incrementing the failed-counter and Requeueing it.
	 *
	 * @param int $id ID of task
	 * @param string|null $failureMessage Optional message to append to the failure_message field.
	 * @return bool Success
	 */
	public function markJobFailed($id, $failureMessage = null) {
		$db = $this->get($id);
		if ($failureMessage === null) {
			$failureMessage = $db->failure_message;
		}
		$fields = [
			'failed = failed + 1',
			'failure_message' => $failureMessage,
		];
		$conditions = [
			'id' => $id,
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
				'failure_message',
			],
			'conditions' => [
				'completed IS' => null,
			],
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
			'completed <' => time() - Configure::read('Queue.cleanuptimeout'),
		]);
		if (!($pidFilePath = Configure::read('Queue.pidfilepath'))) {
			return;
		}
		// Remove all old pid files left over
		$timeout = time() - 2 * Configure::read('Queue.cleanuptimeout');
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

	/**
	 * QueuedTask::lastRun()
	 *
	 * @deprecated ?
	 * @return array
	 */
	public function lastRun() {
		$workerFileLog = LOGS . 'queue' . DS . 'runworker.txt';
		if (file_exists($workerFileLog)) {
			$worker = file_get_contents($workerFileLog);
		}
		return [
			'worker' => isset($worker) ? $worker : '',
			'queue' => $this->field('completed', ['completed IS NOT' => null], ['completed' => 'DESC']),
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
				'reference',
				'status',
				'progress',
				'failure_message',
			];
			if (isset($query['conditions']['exclude'])) {
				$exclude = $query['conditions']['exclude'];
				unset($query['conditions']['exclude']);
				$exclude = trim($exclude, ',');
				$exclude = explode(',', $exclude);
				$query['conditions'][] = [
					'NOT' => [
						'reference' => $exclude,
					],
				];
			}
			if (isset($query['conditions']['task_group'])) {
				$query['conditions'][]['task_group'] = $query['conditions']['task_group'];
				unset($query['conditions']['task_group']);
			}
			return $query;
		}
		// state === after
		foreach ($results as $k => $result) {
			$results[$k] = [
				'reference' => $result['reference'],
				'status' => $result['status'],
			];
			if (!empty($result['progress'])) {
				$results[$k]['progress'] = $result['progress'];
			}
			if (!empty($result['failure_message'])) {
				$results[$k]['failure_message'] = $result['failure_message'];
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
		$x = $this->_connection->query('SELECT max(id) as id FROM `' . $this->table() . '`
	WHERE completed is NULL
	GROUP BY data
	HAVING COUNT(id) > 1');

		$start = 0;
		$x = array_keys($x);
		$numX = count($x);
		while ($start <= $numX) {
			$this->deleteAll([
				'id' => array_slice($x, $start, 10),
			]);
			//debug(array_slice($x, $start, 10));
			$start = $start + 100;
		}
	}

	/**
	 * Generates a unique Identifier for the current worker thread.
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
	 * Resets worker Identifier
	 *
	 * @return void
	 */
	public function clearKey() {
		$this->_key = null;
	}

	/**
	 * truncate()
	 *
	 * @return void
	 */
	public function truncate() {
		$sql = $this->schema()->truncateSql($this->_connection);
		foreach ($sql as $snippet) {
			$this->_connection->execute($snippet);
		}
	}

}
