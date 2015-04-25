<?php
namespace Queue\Controller\Admin;

use Cake\Core\Configure;
use Queue\Controller\AppController;
use Cake\Event\Event;

class QueueController extends AppController {

	public $modelClass = 'Queue.QueuedTask';

	/**
	 * QueueController::beforeFilter()
	 *
	 * @return void
	 */
	public function beforeFilter(Event $event)
	{
		$this->QueuedTask->initConfig();

		parent::beforeFilter($event);
	}

	/**
	 * Admin center.
	 * Manage queues from admin backend (without the need to open ssh console window).
	 *
	 * @return void
	 */
	public function index()
	{
		$status = $this->_status();

		$current = $this->QueuedTask->getLength();
		$pendingDetails = $this->QueuedTask->getPendingStats();
		$data = $this->QueuedTask->getStats();

		$this->set(compact('current', 'data', 'pendingDetails', 'status'));
		$this->helpers[] = 'Tools.Format';
		$this->helpers[] = 'Tools.Datetime';
	}

	/**
	 * Truncate the queue list / table.
	 *
	 * @return void
	 * @throws MethodNotAllowedException when not posted
	 */
	public function reset()
	{
		$this->request->allowMethod('post');
		$res = $this->QueuedTask->truncate();

		if ($res) {
			$message = __d('queue', 'OK');
			$class = 'success';
		} else {
			$message = __d('queue', 'Error');
			$class = 'error';
		}

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
	protected function _status()
	{
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
