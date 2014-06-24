<?php
App::uses('QueueAppController', 'Queue.Controller');

class QueueController extends QueueAppController {

	public $uses = array('Queue.QueuedTask');

/**
 * QueueController::beforeFilter()
 *
 * @return void
 */
	public function beforeFilter() {
		$this->QueuedTask->initConfig();

		parent::beforeFilter();
	}

/**
 * Admin center.
 * Manage queues from admin backend (without the need to open ssh console window).
 *
 * @return void
 */
	public function admin_index() {
		$status = $this->_status();

		$current = $this->QueuedTask->getLength();
		$data = $this->QueuedTask->getStats();

		$this->set(compact('current', 'data', 'status'));
	}

/**
 * Truncate the queue list / table.
 *
 * @return void
 * @throws MethodNotAllowedException when not posted
 */
	public function admin_reset() {
		if (!$this->Common->isPosted()) {
			throw new MethodNotAllowedException();
		}
		$res = $this->QueuedTask->truncate();
		if ($res) {
			$this->Common->flashMessage('OK', 'success');
		} else {
			$this->Common->flashMessage(__('Error'), 'success');
		}
		return $this->Common->autoPostRedirect(array('action' => 'index'));
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
			return array();
		}
		$file = $pidFilePath . 'queue.pid';
		if (!file_exists($file)) {
			return array();
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

		$res = array(
			'time' => filemtime($file),
			'workers' => $count,
		);
		return $res;
	}

}
