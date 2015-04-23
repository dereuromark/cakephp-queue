<?php
namespace Queue\Controller\Admin;

use Queue\Controller\QueueAppController;

class CronTasksController extends QueueAppController {

	/**
	 * admin_index action
	 *
	 * @return void
	 */
	public function index()
	{
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
	public function view($id = null)
	{
		if (empty($id) || !($cronTask = $this->CronTask->find('first', ['conditions' => ['CronTask.id' => $id]]))) {
			$this->Flash->message(__d('queue', 'invalid record'), 'error');
			return $this->Common->autoRedirect(['action' => 'index']);
		}
		$this->set(compact('cronTask'));
	}

	/**
	 * admin_add action
	 *
	 * @return void
	 */
	public function add()
	{
		if ($this->Common->isPosted()) {
			$this->CronTask->create();
			if ($this->CronTask->save($this->request->data)) {
				$var = $this->request->data['CronTask']['title'];
				$this->Flash->message(__d('queue', 'record add %s saved', h($var)), 'success');
				return $this->Common->postRedirect(['action' => 'index']);
			} else {
				$this->Flash->message(__d('queue', 'formContainsErrors'), 'error');
			}
		}
	}

	/**
	 * admin_edit action
	 *
	 * @param int $id CronTask ID
	 * @return void
	 */
	public function edit($id = null)
	{
		if (empty($id) || !($cronTask = $this->CronTask->find('first', ['conditions' => ['CronTask.id' => $id]]))) {
			$this->Flash->message(__d('queue', 'invalid record'), 'error');
			return $this->Common->autoRedirect(['action' => 'index']);
		}
		if ($this->Common->isPosted()) {
			if ($this->CronTask->save($this->request->data)) {
				$var = $this->request->data['CronTask']['title'];
				$this->Flash->message(__d('queue', 'record edit %s saved', h($var)), 'success');
				return $this->Common->postRedirect(['action' => 'index']);
			} else {
				$this->Flash->message(__d('queue', 'formContainsErrors'), 'error');
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
	public function delete($id = null)
	{
		$this->request->allowMethod('post');
		if (empty($id) || !($cronTask = $this->CronTask->find('first', ['conditions' => ['CronTask.id' => $id], 'fields' => ['id', 'title']]))) {
			$this->Flash->message(__d('queue', 'invalid record'), 'error');
			return $this->Common->autoRedirect(['action' => 'index']);
		}
		$var = $cronTask['CronTask']['title'];

		if ($this->CronTask->delete($id)) {
			$this->Flash->message(__d('queue', 'record del %s done', h($var)), 'success');
			return $this->redirect(['action' => 'index']);
		}
		$this->Flash->message(__d('queue', 'record del %s not done exception', h($var)), 'error');
		return $this->Common->autoRedirect(['action' => 'index']);
	}

}
