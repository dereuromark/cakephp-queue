<?php
App::uses('QueueAppController', 'Queue.Controller');

class CronTasksController extends QueueAppController {

	public $paginate = array();

	public function beforeFilter() {
		parent::beforeFilter();
	}



/****************************************************************************************
 * USER functions
 ****************************************************************************************/

	public function index() {
		$this->CronTask->recursive = 0;
		$cronTasks = $this->paginate();
		$this->set(compact('cronTasks'));
	}

	public function view($id = null) {
		if (empty($id) || !($cronTask = $this->CronTask->find('first', array('conditions'=>array('CronTask.id'=>$id))))) {
			$this->Common->flashMessage(__('invalid record'), 'error');
			$this->Common->autoRedirect(array('action' => 'index'));
		}
		$this->set(compact('cronTask'));
	}

	public function add() {
		if (!empty($this->request->data)) {
			$this->CronTask->create();
			if ($this->CronTask->save($this->request->data)) {
				$var = $this->request->data['CronTask']['title'];
				$this->Common->flashMessage(sprintf(__('record add %s saved'), h($var)), 'success');
				$this->Common->postRedirect(array('action' => 'index'));
			} else {
				$this->Common->flashMessage(__('formContainsErrors'), 'error');
			}
		}
	}

	public function edit($id = null) {
		if (empty($id) || !($cronTask = $this->CronTask->find('first', array('conditions'=>array('CronTask.id'=>$id))))) {
			$this->Common->flashMessage(__('invalid record'), 'error');
			$this->Common->autoRedirect(array('action' => 'index'));
		}
		if (!empty($this->request->data)) {
			if ($this->CronTask->save($this->request->data)) {
				$var = $this->request->data['CronTask']['title'];
				$this->Common->flashMessage(sprintf(__('record edit %s saved'), h($var)), 'success');
				$this->Common->postRedirect(array('action' => 'index'));
			} else {
				$this->Common->flashMessage(__('formContainsErrors'), 'error');
			}
		}
		if (empty($this->request->data)) {
			$this->request->data = $cronTask;
		}
	}

	public function delete($id = null) {
		if (empty($id) || !($cronTask = $this->CronTask->find('first', array('conditions'=>array('CronTask.id'=>$id), 'fields'=>array('id', 'title'))))) {
			$this->Common->flashMessage(__('invalid record'), 'error');
			$this->Common->autoRedirect(array('action'=>'index'));
		}
		$var = $cronTask['CronTask']['title'];
		
		if ($this->CronTask->delete($id)) {
			$this->Common->flashMessage(sprintf(__('record del %s done'), h($var)), 'success');
			$this->redirect(array('action' => 'index'));
		}
		$this->Common->flashMessage(sprintf(__('record del %s not done exception'), h($var)), 'error');
		$this->Common->autoRedirect(array('action' => 'index'));
	}



/****************************************************************************************
 * ADMIN functions
 ****************************************************************************************/

	public function admin_index() {
		$this->CronTask->recursive = 0;
		$cronTasks = $this->paginate();
		$this->set(compact('cronTasks'));
	}

	public function admin_view($id = null) {
		if (empty($id) || !($cronTask = $this->CronTask->find('first', array('conditions'=>array('CronTask.id'=>$id))))) {
			$this->Common->flashMessage(__('invalid record'), 'error');
			$this->Common->autoRedirect(array('action' => 'index'));
		}
		$this->set(compact('cronTask'));
	}

	public function admin_add() {
		if (!empty($this->request->data)) {
			$this->CronTask->create();
			if ($this->CronTask->save($this->request->data)) {
				$var = $this->request->data['CronTask']['title'];
				$this->Common->flashMessage(sprintf(__('record add %s saved'), h($var)), 'success');
				$this->Common->postRedirect(array('action' => 'index'));
			} else {
				$this->Common->flashMessage(__('formContainsErrors'), 'error');
			}
		}
	}

	public function admin_edit($id = null) {
		if (empty($id) || !($cronTask = $this->CronTask->find('first', array('conditions'=>array('CronTask.id'=>$id))))) {
			$this->Common->flashMessage(__('invalid record'), 'error');
			$this->Common->autoRedirect(array('action' => 'index'));
		}
		if (!empty($this->request->data)) {
			if ($this->CronTask->save($this->request->data)) {
				$var = $this->request->data['CronTask']['title'];
				$this->Common->flashMessage(sprintf(__('record edit %s saved'), h($var)), 'success');
				$this->Common->postRedirect(array('action' => 'index'));
			} else {
				$this->Common->flashMessage(__('formContainsErrors'), 'error');
			}
		}
		if (empty($this->request->data)) {
			$this->request->data = $cronTask;
		}
	}

	public function admin_delete($id = null) {
		if (empty($id) || !($cronTask = $this->CronTask->find('first', array('conditions'=>array('CronTask.id'=>$id), 'fields'=>array('id', 'title'))))) {
			$this->Common->flashMessage(__('invalid record'), 'error');
			$this->Common->autoRedirect(array('action'=>'index'));
		}
		$var = $cronTask['CronTask']['title'];
		
		if ($this->CronTask->delete($id)) {
			$this->Common->flashMessage(sprintf(__('record del %s done'), h($var)), 'success');
			$this->redirect(array('action' => 'index'));
		}
		$this->Common->flashMessage(sprintf(__('record del %s not done exception'), h($var)), 'error');
		$this->Common->autoRedirect(array('action' => 'index'));
	}



/****************************************************************************************
 * protected/interal functions
 ****************************************************************************************/


/****************************************************************************************
 * deprecated/test functions
 ****************************************************************************************/

}
