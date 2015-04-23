<?php
namespace Queue\Controller;

class CronTasksController extends AppController {

	public $paginate = [];

	/**
	 * index action
	 *
	 * @return void
	 */
	public function index()
	{
		$cronTasks = $this->paginate();
		$this->set(compact('cronTasks'));
	}

	/**
	 * view action
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
	 * add action
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
	 * edit action
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
	 * delete action
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
