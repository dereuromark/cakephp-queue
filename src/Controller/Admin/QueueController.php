<?php

namespace Queue\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Exception\NotFoundException;
use Queue\Queue\TaskFinder;

/**
 * @property \Queue\Model\Table\QueuedJobsTable $QueuedJobs
 * @property \Queue\Model\Table\QueueProcessesTable $QueueProcesses
 */
class QueueController extends AppController {

	/**
	 * @var string
	 */
	public $modelClass = 'Queue.QueuedJobs';

	/**
	 * QueueController::beforeFilter()
	 *
	 * @param \Cake\Event\Event $event
	 * @return \Cake\Http\Response|null
	 */
	public function beforeFilter(Event $event) {
		$this->QueuedJobs->initConfig();

		parent::beforeFilter($event);
	}

	/**
	 * Admin center.
	 * Manage queues from admin backend (without the need to open ssh console window).
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function index() {
		$status = $this->_status();

		$current = $this->QueuedJobs->getLength();
		$pendingDetails = $this->QueuedJobs->getPendingStats();
		$data = $this->QueuedJobs->getStats();

		$taskFinder = new TaskFinder();
		$tasks = $taskFinder->allAppAndPluginTasks();

		$this->set(compact('current', 'data', 'pendingDetails', 'status', 'tasks'));
		$this->helpers[] = 'Tools.Format';
		$this->helpers[] = 'Tools.Time';
	}

	/**
	 * @param string|null $job
	 *
	 * @return \Cake\Http\Response
	 */
	public function addJob($job = null) {
		if (!$job) {
			throw new NotFoundException();
		}

		$this->QueuedJobs->createJob($job);

		$this->Flash->success('Job ' . $job . ' added');

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * @param string|null $id
	 *
	 * @return \Cake\Http\Response
	 */
	public function resetJob($id = null) {
		if (!$id) {
			throw new NotFoundException();
		}

		$this->QueuedJobs->reset($id);

		$this->Flash->success('Job # ' . $id . ' re-added');

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * @param string|null $id
	 *
	 * @return \Cake\Http\Response
	 */
	public function removeJob($id = null) {
		$queuedJob = $this->QueuedJobs->get($id);

		$this->QueuedJobs->delete($queuedJob);

		$this->Flash->success('Job # ' . $id . ' deleted');

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function processes() {
		$processes = $this->QueuedJobs->getProcesses();

		if ($this->request->is('post') && $this->request->query('kill')) {
			$pid = $this->request->query('kill');
			$this->QueuedJobs->terminateProcess($pid);

			return $this->redirect(['action' => 'processes']);
		}

		$this->set(compact('processes'));
	}

	/**
	 * Mark all failed jobs as ready for re-run.
	 *
	 * @return \Cake\Http\Response
	 * @throws \Cake\Network\Exception\MethodNotAllowedException when not posted
	 */
	public function reset() {
		$this->request->allowMethod('post');
		$this->QueuedJobs->reset();

		$message = __d('queue', 'OK');
		$this->Flash->success($message);

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * Truncate the queue list / table.
	 *
	 * @return \Cake\Http\Response
	 * @throws \Cake\Network\Exception\MethodNotAllowedException when not posted
	 */
	public function hardReset() {
		$this->request->allowMethod('post');
		$this->QueuedJobs->truncate();

		$message = __d('queue', 'OK');
		$this->Flash->success($message);

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * QueueController::_status()
	 *
	 * If pid loggin is enabled, will return an array with
	 * - time: int Timestamp
	 * - workers: int Count of currently running workers
	 *
	 * @return array Status array
	 */
	protected function _status() {
		$timeout = Configure::read('Queue.defaultworkertimeout');
		$thresholdTime = time() - $timeout;

		$pidFilePath = Configure::read('Queue.pidfilepath');
		if (!$pidFilePath) {
			$this->loadModel('Queue.QueueProcesses');
			$results = $this->QueueProcesses->find()->where(['modified >' => $thresholdTime])->orderDesc('modified')->hydrate(false)->all()->toArray();

			if (!$results) {
				return [];
			}

			$count = count($results);
			$record = array_shift($results);
			/** @var \Cake\I18n\FrozenTime $time */
			$time = $record['modified'];

			return [
				'time' => (int)$time->toUnixString(),
				'workers' => $count,
			];
		}

		$file = $pidFilePath . 'queue.pid';
		if (!file_exists($file)) {
			return [];
		}

		$count = 0;
		foreach (glob($pidFilePath . 'queue_*.pid') as $filename) {
			$time = filemtime($filename);
			if ($time >= $thresholdTime) {
				$count++;
			}
		}

		$res = [
			'time' => filemtime($file),
			'workers' => $count,
		];
		return $res;
	}

}
