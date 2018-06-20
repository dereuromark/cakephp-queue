<?php
namespace Queue\Controller\Admin;

use App\Controller\AppController;

/**
 * @property \Queue\Model\Table\QueuedJobsTable $QueuedJobs
 *
 * @method \Queue\Model\Entity\QueuedJob[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class QueuedJobsController extends AppController {

	/**
	 * @var array
	 */
	public $paginate = [
		'order' => [
			'created' => 'DESC',
		],
	];

	/**
	 * Index method
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function index() {
		$queuedJobs = $this->paginate();

		$this->set(compact('queuedJobs'));
		$this->helpers[] = 'Tools.Format';
		$this->helpers[] = 'Tools.Time';
	}

	/**
	 * View method
	 *
	 * @param string|null $id Queued Job id.
	 * @return \Cake\Http\Response|null
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function view($id = null) {
		$queuedJob = $this->QueuedJobs->get($id, [
			'contain' => []
		]);

		$this->set(compact('queuedJob'));
	}

	/**
	 * Edit method
	 *
	 * @param string|null $id Queued Job id.
	 * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
	 * @throws \Cake\Network\Exception\NotFoundException When record not found.
	 */
	public function edit($id = null) {
		$queuedJob = $this->QueuedJobs->get($id, [
			'contain' => []
		]);
		if ($queuedJob->completed) {
			$this->Flash->error(__('The queued job is already completed.'));
			return $this->redirect(['action' => 'index']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$queuedJob = $this->QueuedJobs->patchEntity($queuedJob, $this->request->getData());
			if ($this->QueuedJobs->save($queuedJob)) {
				$this->Flash->success(__('The queued job has been saved.'));
				return $this->redirect(['action' => 'index']);
			}

			$this->Flash->error(__('The queued job could not be saved. Please, try again.'));
		}

		$this->set(compact('queuedJob'));
	}

	/**
	 * Delete method
	 *
	 * @param string|null $id Queued Job id.
	 * @return \Cake\Http\Response|null Redirects to index.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function delete($id = null) {
		$this->request->allowMethod(['post', 'delete']);
		$queuedJob = $this->QueuedJobs->get($id);
		if ($this->QueuedJobs->delete($queuedJob)) {
			$this->Flash->success(__('The queued job has been deleted.'));
		} else {
			$this->Flash->error(__('The queued job could not be deleted. Please, try again.'));
		}
		return $this->redirect(['action' => 'index']);
	}

}
