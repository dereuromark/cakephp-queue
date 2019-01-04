<?php
namespace Queue\Controller\Admin;

use App\Controller\AppController;

/**
 * @property \Queue\Model\Table\QueueProcessesTable $QueueProcesses
 *
 * @method \Queue\Model\Entity\QueueProcess[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class QueueProcessesController extends AppController {

	/**
	 * Index method
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function index() {
		$queueProcesses = $this->paginate();

		$this->set(compact('queueProcesses'));
		$this->helpers[] = 'Tools.Format';
		$this->helpers[] = 'Tools.Time';
		$this->helpers[] = 'Shim.Configure';
	}

	/**
	 * View method
	 *
	 * @param string|null $id Queue Process id.
	 * @return \Cake\Http\Response|null
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function view($id = null) {
		$queueProcess = $this->QueueProcesses->get($id, [
			'contain' => []
		]);

		$this->set(compact('queueProcess'));
		$this->helpers[] = 'Tools.Format';
		$this->helpers[] = 'Tools.Time';
	}

	/**
	 * Edit method
	 *
	 * @param string|null $id Queue Process id.
	 * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
	 * @throws \Cake\Http\Exception\NotFoundException When record not found.
	 */
	public function edit($id = null) {
		$queueProcess = $this->QueueProcesses->get($id, [
			'contain' => []
		]);
		if ($this->request->is(['patch', 'post', 'put'])) {
			$queueProcess = $this->QueueProcesses->patchEntity($queueProcess, $this->request->getData());
			if ($this->QueueProcesses->save($queueProcess)) {
				$this->Flash->success(__('The queue process has been saved.'));
				return $this->redirect(['action' => 'index']);
			}

			$this->Flash->error(__('The queue process could not be saved. Please, try again.'));
		}

		$this->set(compact('queueProcess'));
	}

	/**
	 * Delete method
	 *
	 * @param string|null $id Queue Process id.
	 * @return \Cake\Http\Response|null Redirects to index.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function delete($id = null) {
		$this->request->allowMethod(['post', 'delete']);
		$queueProcess = $this->QueueProcesses->get($id);
		if ($this->QueueProcesses->delete($queueProcess)) {
			$this->Flash->success(__('The queue process has been deleted.'));
		} else {
			$this->Flash->error(__('The queue process could not be deleted. Please, try again.'));
		}
		return $this->redirect(['action' => 'index']);
	}

	/**
	 * @return \Cake\Http\Response|null Redirects to index.
	 */
	public function cleanup() {
		$this->request->allowMethod(['post', 'delete']);

		$count = $this->QueueProcesses->cleanKilledProcesses();

		$this->Flash->success($count . ' leftovers cleaned out.');

		return $this->redirect($this->referer(['action' => 'index'], true));
	}

}
