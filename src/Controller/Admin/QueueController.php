<?php

namespace Queue\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Event\Event;

/**
 * @property \Queue\Model\Table\QueuedJobsTable $QueuedJobs
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
	 * @return void
	 */
	public function beforeFilter(Event $event) {
		$this->QueuedJobs->initConfig();

		parent::beforeFilter($event);
	}

	/**
	 * Admin center.
	 * Manage queues from admin backend (without the need to open ssh console window).
	 *
	 * @return void
	 */
	public function index() {
		$status = $this->_status();

		$current = $this->QueuedJobs->getLength();
		$pendingDetails = $this->QueuedJobs->getPendingStats();
		$data = $this->QueuedJobs->getStats();

		$this->set(compact('current', 'data', 'pendingDetails', 'status'));
		$this->helpers[] = 'Tools.Format';
	}

	/**
	 * @return \Cake\Network\Response|null|void
	 */
	public function processes() {
		$processes = $this->QueuedJobs->getProcesses();

		if ($this->request->is('post') && $this->request->query('kill')) {
			$pid = $this->request->query('kill');
			$this->QueuedJobs->terminateProcess($pid, 9);

			return $this->redirect(['action' => 'processes']);
		}

		$this->set(compact('processes'));
	}

	/**
	 * Truncate the queue list / table.
	 *
	 * @return \Cake\Network\Response
	 * @throws \Cake\Network\Exception\MethodNotAllowedException when not posted
	 */
	public function reset() {
		$this->request->allowMethod('post');
		$this->QueuedJobs->truncate();

		$message = __d('queue', 'OK');
		$class = 'success';

		$this->Flash->message($message, $class);

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
		if (!($pidFilePath = Configure::read('Queue.pidfilepath'))) {
			return [];
		}
		$file = $pidFilePath . 'queue.pid';
		if (!file_exists($file)) {
			return [];
		}

		$sleepTime = Configure::read('Queue.sleeptime');
		$thresholdTime = time() - $sleepTime;
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
