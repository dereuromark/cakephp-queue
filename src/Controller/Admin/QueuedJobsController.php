<?php
namespace Queue\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Plugin;

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
	 * @return void
	 */
	public function initialize()
	{
		parent::initialize();

		if (!Plugin::loaded('Search')) {
			return;
		}
		$this->loadComponent('Search.Prg', [
			'actions' => ['index'],
		]);
	}

	/**
	 * Index method
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function index() {
		if (Plugin::loaded('Search')) {
			$query = $this->QueuedJobs->find('search', ['search' => $this->request->getQuery()]);
		} else {
			$query = $this->QueuedJobs->find();
		}
		$queuedJobs = $this->paginate($query);

		$this->set(compact('queuedJobs'));
		$this->helpers[] = 'Tools.Format';
		$this->helpers[] = 'Tools.Time';

		if (Plugin::loaded('Search')) {
			$jobTypes = $this->QueuedJobs->find()->where()->find('list', ['keyField' => 'job_type', 'valueField' => 'job_type'])->distinct('job_type')->toArray();
			$this->set(compact('jobTypes'));
		}
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
