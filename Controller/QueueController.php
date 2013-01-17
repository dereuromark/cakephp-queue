<?php
App::uses('QueueAppController', 'Queue.Controller');

class QueueController extends QueueAppController {

	public $uses = array('Queue.QueuedTask');

	public $helpers = array('Tools.Format', 'Tools.Datetime');

	/**
	 * admin center
	 * manage queues from admin backend (without the need to open ssh console window)
	 *
	 * 2012-01-24 ms
	 */
	public function admin_index() {
		$types = $this->QueuedTask->getTypes();

		$tasks = array();
		foreach ($types as $type) {
			$tasks[$type] = $this->QueuedTask->getLength($type);
		}
		# Total unfinished Jobs
		$allTasks = $this->QueuedTask->getLength();

		$details = array();
		$details['worker'] = filemtime(TMP.'queue_notification.txt');

		$this->set(compact('tasks', 'allTasks', 'details'));
	}

	/**
	 * truncate the queue list / table
	 * 2012-01-24 ms
	 */
	public function admin_reset() {
		$this->QueuedTask->truncate();
		$this->Common->flashMessage(__('Reset done'), 'success');
		$this->Common->autoRedirect(array('action'=>'index'));
	}

}

