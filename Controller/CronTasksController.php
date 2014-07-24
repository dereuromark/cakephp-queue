<?php
App::uses('QueueAppController', 'Queue.Controller');

class CronTasksController extends QueueAppController {

	public $paginate = array();

/**
 * beforeFilter action
 *
 * @return void
 */
	public function beforeFilter() {
		parent::beforeFilter();
	}

/**
 * index action
 *
 * @return void
 */
	public function index() {
		$this->CronTask->recursive = 0;
		$cronTasks = $this->paginate();
		$this->set(compact('cronTasks'));
	}

/**
 * view action
 *
 * @param int $id CronTask ID
 * @return void
 */
	public function view($id = null) {
		if (empty($id) || !($cronTask = $this->CronTask->find('first', array('conditions' => array('CronTask.id' => $id))))) {
			$this->Common->flashMessage(__('invalid record'), 'error');
			return $this->Common->autoRedirect(array('action' => 'index'));
		}
		$this->set(compact('cronTask'));
	}

/**
 * add action
 *
 * @return void
 */
	public function add() {
		if ($this->Common->isPosted()) {
			$this->CronTask->create();
			if ($this->CronTask->save($this->request->data)) {
				$var = $this->request->data['CronTask']['title'];
				$this->Common->flashMessage(__('record add %s saved', h($var)), 'success');
				return $this->Common->postRedirect(array('action' => 'index'));
			} else {
				$this->Common->flashMessage(__('formContainsErrors'), 'error');
			}
		}
	}

/**
 * edit action
 *
 * @param int $id CronTask ID
 * @return void
 */
	public function edit($id = null) {
		if (empty($id) || !($cronTask = $this->CronTask->find('first', array('conditions' => array('CronTask.id' => $id))))) {
			$this->Common->flashMessage(__('invalid record'), 'error');
			return $this->Common->autoRedirect(array('action' => 'index'));
		}
		if ($this->Common->isPosted()) {
			if ($this->CronTask->save($this->request->data)) {
				$var = $this->request->data['CronTask']['title'];
				$this->Common->flashMessage(__('record edit %s saved', h($var)), 'success');
				return $this->Common->postRedirect(array('action' => 'index'));
			} else {
				$this->Common->flashMessage(__('formContainsErrors'), 'error');
			}
		}
		if (empty($this->request->data)) {
			$this->request->data = $cronTask;
		}
	}

/**
 * delete action
 *
 * @param int $id CronTask ID
 * @return void
 * @throws MethodNotAllowedException when method is not POST
 */
	public function delete($id = null) {
		if (!$this->Common->isPosted()) {
			throw new MethodNotAllowedException();
		}
		if (empty($id) || !($cronTask = $this->CronTask->find('first', array('conditions' => array('CronTask.id' => $id), 'fields' => array('id', 'title'))))) {
			$this->Common->flashMessage(__('invalid record'), 'error');
			return $this->Common->autoRedirect(array('action' => 'index'));
		}
		$var = $cronTask['CronTask']['title'];

		if ($this->CronTask->delete($id)) {
			$this->Common->flashMessage(__('record del %s done', h($var)), 'success');
			return $this->redirect(array('action' => 'index'));
		}
		$this->Common->flashMessage(__('record del %s not done exception', h($var)), 'error');
		return $this->Common->autoRedirect(array('action' => 'index'));
	}

/**
 * admin_index action
 *
 * @return void
 */
	public function admin_index() {
		$this->CronTask->recursive = 0;
		$cronTasks = $this->paginate();
		$this->set(compact('cronTasks'));
	}

/**
 * admin_view action
 *
 * @param int $id CronTask ID
 * @return void
 */
	public function admin_view($id = null) {
		if (empty($id) || !($cronTask = $this->CronTask->find('first', array('conditions' => array('CronTask.id' => $id))))) {
			$this->Common->flashMessage(__('invalid record'), 'error');
			return $this->Common->autoRedirect(array('action' => 'index'));
		}
		$this->set(compact('cronTask'));
	}

/**
 * admin_add action
 *
 * @return void
 */
	public function admin_add() {
		if ($this->Common->isPosted()) {
			$this->CronTask->create();
			if ($this->CronTask->save($this->request->data)) {
				$var = $this->request->data['CronTask']['title'];
				$this->Common->flashMessage(__('record add %s saved', h($var)), 'success');
				return $this->Common->postRedirect(array('action' => 'index'));
			} else {
				$this->Common->flashMessage(__('formContainsErrors'), 'error');
			}
		}
	}

/**
 * admin_edit action
 *
 * @param int $id CronTask ID
 * @return void
 */
	public function admin_edit($id = null) {
		if (empty($id) || !($cronTask = $this->CronTask->find('first', array('conditions' => array('CronTask.id' => $id))))) {
			$this->Common->flashMessage(__('invalid record'), 'error');
			return $this->Common->autoRedirect(array('action' => 'index'));
		}
		if ($this->Common->isPosted()) {
			if ($this->CronTask->save($this->request->data)) {
				$var = $this->request->data['CronTask']['title'];
				$this->Common->flashMessage(__('record edit %s saved', h($var)), 'success');
				return $this->Common->postRedirect(array('action' => 'index'));
			} else {
				$this->Common->flashMessage(__('formContainsErrors'), 'error');
			}
		}
		if (empty($this->request->data)) {
			$this->request->data = $cronTask;
		}
	}
/**
 * admin_delete action
 *
 * @param int $id CronTask ID
 * @return void
 * @throws MethodNotAllowedException when method is not POST
 */
	public function admin_delete($id = null) {
		if (!$this->Common->isPosted()) {
			throw new MethodNotAllowedException();
		}
		if (empty($id) || !($cronTask = $this->CronTask->find('first', array('conditions' => array('CronTask.id' => $id), 'fields' => array('id', 'title'))))) {
			$this->Common->flashMessage(__('invalid record'), 'error');
			return $this->Common->autoRedirect(array('action' => 'index'));
		}
		$var = $cronTask['CronTask']['title'];

		if ($this->CronTask->delete($id)) {
			$this->Common->flashMessage(__('record del %s done', h($var)), 'success');
			return $this->redirect(array('action' => 'index'));
		}
		$this->Common->flashMessage(__('record del %s not done exception', h($var)), 'error');
		return $this->Common->autoRedirect(array('action' => 'index'));
	}

}
