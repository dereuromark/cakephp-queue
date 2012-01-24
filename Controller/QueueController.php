<?php
App::uses('QueueAppController', 'Queue.Controller');

class QueueController extends QueueAppController {

	public $uses = array('Queue.QueuedTask');

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
			//$this->out("      " . str_pad($type, 20, ' ', STR_PAD_RIGHT) . ": " . $this->QueuedTask->getLength($type));
			$tasks[$type] = $this->QueuedTask->getLength($type);
		}
		//$this->hr();
		//$this->out('Total unfinished Jobs      : ' . $this->QueuedTask->getLength());
		$allTasks = $this->QueuedTask->getLength();
		
		$details = array();
		$details['worker'] = filemtime(TMP.'queue_notification.txt');
		
		$this->set(compact('tasks', 'allTasks', 'details'));
	}

}

