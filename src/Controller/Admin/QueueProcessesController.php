<?php

namespace Queue\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Configure;
use Exception;

/**
 * @property \Queue\Model\Table\QueueProcessesTable $QueueProcesses
 *
 * @method \Cake\Datasource\ResultSetInterface<\Queue\Model\Entity\QueueProcess> paginate($object = null, array $settings = [])
 * @property \Queue\Model\Table\QueuedJobsTable $QueuedJobs
 */
#[\AllowDynamicProperties]
class QueueProcessesController extends AppController {

	use LoadHelperTrait;

	/**
	 * @var array<mixed>
	 */
	public $paginate = [
		'order' => [
			'created' => 'DESC',
		],
	];

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadHelpers();
	}

	/**
	 * Index method
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		$queueProcesses = $this->paginate()->toArray();

		$this->set(compact('queueProcesses'));
	}

	/**
	 * View method
	 *
	 * @param int|null $id Queue Process id.
	 * @return \Cake\Http\Response|null|void
	 */
	public function view($id = null) {
		$queueProcess = $this->QueueProcesses->get($id, [
			'contain' => [],
		]);

		$this->set(compact('queueProcess'));
	}

	/**
	 * Edit method
	 *
	 * @param int|null $id Queue Process id.
	 * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
	 */
	public function edit($id = null) {
		$queueProcess = $this->QueueProcesses->get($id, [
			'contain' => [],
		]);
		if ($this->request->is(['patch', 'post', 'put'])) {
			$queueProcess = $this->QueueProcesses->patchEntity($queueProcess, $this->request->getData());
			if ($this->QueueProcesses->save($queueProcess)) {
				$this->Flash->success(__d('queue', 'The queue process has been saved.'));

				return $this->redirect(['action' => 'index']);
			}

			$this->Flash->error(__d('queue', 'The queue process could not be saved. Please, try again.'));
		}

		$this->set(compact('queueProcess'));
	}

	/**
	 * @param int|null $id Queue Process id.
	 * @return \Cake\Http\Response|null|void Redirects to index.
	 */
	public function terminate($id = null) {
		$this->request->allowMethod(['post', 'delete']);

		try {
			$queueProcess = $this->QueueProcesses->get($id);
			$queueProcess->terminate = true;
			$this->QueueProcesses->saveOrFail($queueProcess);
			$this->Flash->success(__d('queue', 'The queue process has been deleted.'));
		} catch (Exception $exception) {
			$this->Flash->error(__d('queue', 'The queue process could not be deleted. Please, try again.'));
		}

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * @param int|null $id Queue Process id.
	 * @param int|null $sig Signal (defaults to graceful SIGTERM = 15).
	 * @return \Cake\Http\Response|null|void Redirects to index.
	 */
	public function delete($id = null, $sig = null) {
		$this->request->allowMethod(['post', 'delete']);
		$queueProcess = $this->QueueProcesses->get($id);

		if (!Configure::read('Queue.multiserver')) {
			$this->QueueProcesses->terminateProcess($queueProcess->pid, $sig ? (int)$sig : SIGTERM);
		}

		if ($this->QueueProcesses->delete($queueProcess)) {
			$this->Flash->success(__d('queue', 'The queue process has been deleted.'));
		} else {
			$this->Flash->error(__d('queue', 'The queue process could not be deleted. Please, try again.'));
		}

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * @return \Cake\Http\Response|null|void Redirects to index.
	 */
	public function cleanup() {
		$this->request->allowMethod(['post', 'delete']);

		$count = $this->QueueProcesses->cleanEndedProcesses();

		$this->Flash->success($count . ' leftovers cleaned out.');

		return $this->redirect($this->referer(['action' => 'index'], true));
	}

}
