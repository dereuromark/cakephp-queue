<?php
namespace Queue\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Http\Exception\NotFoundException;
use Queue\Queue\TaskFinder;

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
	public function initialize() {
		parent::initialize();

		if (!Configure::read('Queue.isSearchEnabled') || !Plugin::loaded('Search')) {
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
		if (Configure::read('Queue.isSearchEnabled') && Plugin::loaded('Search')) {
			$query = $this->QueuedJobs->find('search', ['search' => $this->request->getQuery()]);
		} else {
			$query = $this->QueuedJobs->find();
		}
		$queuedJobs = $this->paginate($query);

		$this->set(compact('queuedJobs'));
		$this->helpers[] = 'Tools.Format';
		$this->helpers[] = 'Tools.Time';

		if (Configure::read('Queue.isSearchEnabled') && Plugin::loaded('Search')) {
			$jobTypes = $this->QueuedJobs->find()->where()->find('list', ['keyField' => 'job_type', 'valueField' => 'job_type'])->distinct('job_type')->toArray();
			$this->set(compact('jobTypes'));
		}
	}

	/**
	 * Index method
	 *
	 * @param string|null $jobType
	 * @return void
	 * @throws \Cake\Http\Exception\NotFoundException
	 */
	public function stats($jobType = null) {
		if (!Configure::read('Queue.isStatisticEnabled')) {
			throw new NotFoundException('Not enabled');
		}

		$stats = $this->QueuedJobs->getFullStats($jobType);

		$jobTypes = $this->QueuedJobs->find()->where()->find('list', ['keyField' => 'job_type', 'valueField' => 'job_type'])->distinct('job_type')->toArray();
		$this->set(compact('stats', 'jobTypes'));
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
	 * @throws \Cake\Http\Exception\NotFoundException When record not found.
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

			$this->Flash->error(__('The queued job could not be saved. Please try again.'));
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
			$this->Flash->error(__('The queued job could not be deleted. Please try again.'));
		}
		return $this->redirect(['action' => 'index']);
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function test() {
		$taskFinder = new TaskFinder();
		$allTasks = $taskFinder->allAppAndPluginTasks();
		$tasks = [];
		foreach ($allTasks as $key => $task) {
			if (substr($task, 0, 11) !== 'Queue.Queue') {
				continue;
			}
			if (substr($task, -7) !== 'Example') {
				continue;
			}

			$name = substr($task, 11);
			$tasks[$name] = $task;
		}

		$queuedJob = $this->QueuedJobs->newEntity();

		if ($this->request->is(['post', 'patch', 'put'])) {
			$queuedJob = $this->QueuedJobs->patchEntity($queuedJob, $this->request->getData());
			$jobType = $queuedJob->job_type;
			$notBefore = $queuedJob->notbefore;

			if ($jobType && isset($tasks[$jobType]) && $notBefore) {
				$config = [
					'notBefore' => $notBefore,
				];

				$this->QueuedJobs->createJob($jobType, null, $config);

				$this->Flash->success(__('The requested job has been queued.'));

				return $this->redirect(['action' => 'test']);
			}

			$this->Flash->error(__('The job could not be queued. Please try again.'));
		}

		$this->set(compact('tasks', 'queuedJob'));
	}

}
