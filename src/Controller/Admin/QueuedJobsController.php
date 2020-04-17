<?php

namespace Queue\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenTime;
use Queue\Queue\TaskFinder;
use RuntimeException;

/**
 * @property \Queue\Model\Table\QueuedJobsTable $QueuedJobs
 *
 * @method \Queue\Model\Entity\QueuedJob[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 * @property \Search\Controller\Component\SearchComponent $Search
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
	public function initialize(): void {
		parent::initialize();

		if (!$this->components()->has('RequestHandler')) {
			$this->loadComponent('RequestHandler', [
				'enableBeforeRedirect' => false,
			]);
		}

		$this->enableSearch();
		$this->viewBuilder()->setHelpers(['Tools.Time', 'Tools.Format', 'Shim.Configure']);
	}

	/**
	 * @return void
	 */
	protected function enableSearch(): void {
		if (Configure::read('Queue.isSearchEnabled') === false || !Plugin::isLoaded('Search')) {
			return;
		}
		if ($this->components()->has('Search')) {
			return;
		}
		$this->loadComponent('Search.Search', [
			'actions' => ['index'],
		]);
	}

	/**
	 * Index method
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		if (Configure::read('Queue.isSearchEnabled') !== false && Plugin::isLoaded('Search')) {
			$query = $this->QueuedJobs->find('search', ['search' => $this->request->getQuery()]);
		} else {
			$query = $this->QueuedJobs->find();
		}
		$queuedJobs = $this->paginate($query);

		$this->set(compact('queuedJobs'));

		if (Configure::read('Queue.isSearchEnabled') !== false && Plugin::isLoaded('Search')) {
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
	 * @param int|null $id Queued Job id.
	 * @return \Cake\Http\Response|null|void
	 */
	public function view($id = null) {
		$queuedJob = $this->QueuedJobs->get((int)$id, [
			'contain' => ['WorkerProcesses'],
		]);

		if ($this->request->getParam('_ext') && $this->request->getParam('_ext') === 'json' && $this->request->getQuery('download')) {
			$this->response = $this->response->withDownload('queued-job-' . $id . '.json');
		}

		$this->set(compact('queuedJob'));
		$this->viewBuilder()->setOption('serialize', ['queuedJob']);
	}

	/**
	 * @throws \RuntimeException
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function import() {
		if ($this->request->is(['post'])) {
			$file = $this->request->getData('file');
			if ($file && $file['error'] == 0 && $file['size'] > 0) {
				$content = file_get_contents($file['tmp_name']);
				$json = json_decode($content, true);
				if (empty($json) || empty($json['queuedJob'])) {
					throw new RuntimeException('Invalid JSON content');
				}

				$data = $json['queuedJob'];

				unset($data['id']);
				$data['created'] = new FrozenTime($data['created']);

				if ($this->request->getData('reset')) {
					$data['fetched'] = null;
					$data['completed'] = null;
					$data['progress'] = null;
					$data['failed'] = 0;
					$data['failure_message'] = null;
					$data['workerkey'] = null;
					$data['status'] = null;
				}

				if ($data['notbefore']) {
					$data['notbefore'] = new FrozenTime($data['notbefore']);
				}
				if ($data['fetched']) {
					$data['fetched'] = new FrozenTime($data['fetched']);
				}
				if ($data['completed']) {
					$data['completed'] = new FrozenTime($data['completed']);
				}

				$queuedJob = $this->QueuedJobs->newEntity($data);
				if ($queuedJob->getErrors()) {
					$this->Flash->error('Validation failed: ' . print_r($queuedJob->getErrors(), true));
					return $this->redirect($this->referer(['action' => 'index']));
				}

				$this->QueuedJobs->saveOrFail($queuedJob);

				$this->Flash->success('Imported');
				return $this->redirect(['action' => 'view', $queuedJob->id]);
			}

			$this->Flash->error(__d('queue', 'Please, try again.'));
		}
	}

	/**
	 * Edit method
	 *
	 * @param int|null $id Queued Job id.
	 * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
	 */
	public function edit($id = null) {
		$queuedJob = $this->QueuedJobs->get($id, [
			'contain' => [],
		]);
		if ($queuedJob->completed) {
			$this->Flash->error(__d('queue', 'The queued job is already completed.'));
			return $this->redirect(['action' => 'view', $id]);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$queuedJob = $this->QueuedJobs->patchEntity($queuedJob, $this->request->getData());
			if ($this->QueuedJobs->save($queuedJob)) {
				$this->Flash->success(__d('queue', 'The queued job has been saved.'));
				return $this->redirect(['action' => 'view', $id]);
			}

			$this->Flash->error(__d('queue', 'The queued job could not be saved. Please try again.'));
		}

		$this->set(compact('queuedJob'));
	}

	/**
	 * @param int|null $id Queued Job id.
	 * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
	 */
	public function data($id = null) {
		return $this->edit($id);
	}

	/**
	 * Delete method
	 *
	 * @param int|null $id Queued Job id.
	 * @return \Cake\Http\Response|null|void Redirects to index.
	 */
	public function delete($id = null) {
		$this->request->allowMethod(['post', 'delete']);
		$queuedJob = $this->QueuedJobs->get($id);
		if ($this->QueuedJobs->delete($queuedJob)) {
			$this->Flash->success(__d('queue', 'The queued job has been deleted.'));
		} else {
			$this->Flash->error(__d('queue', 'The queued job could not be deleted. Please try again.'));
		}
		return $this->redirect(['action' => 'index']);
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 * @throws \Cake\Http\Exception\NotFoundException
	 */
	public function execute() {
		if (!Configure::read('debug')) {
			throw new NotFoundException('Only for local development. Security implications if open on deployment.');
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$data = (array)$this->request->getData();
			if (empty($data['command'])) {
				$this->Flash->error('Command is required');
				return null;
			}

			$amount = $data['amount'];
			unset($data['amount']);
			for ($i = 0; $i < $amount; $i++) {
				$this->QueuedJobs->createJob('Execute', $data);
			}

			$this->Flash->success(__d('queue', 'The requested job has been queued ' . $amount . 'x.'));

			return $this->redirect(['action' => 'execute']);
		}
	}

	/**
	 * @return \Cake\Http\Response|null|void
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

		$queuedJob = $this->QueuedJobs->newEmptyEntity();

		if ($this->request->is(['post', 'patch', 'put'])) {
			$queuedJob = $this->QueuedJobs->patchEntity($queuedJob, $this->request->getData());
			$jobType = $queuedJob->job_type;
			$notBefore = $queuedJob->notbefore;

			if ($jobType && isset($tasks[$jobType]) && $notBefore) {
				$config = [
					'notBefore' => $notBefore,
				];

				$this->QueuedJobs->createJob($jobType, null, $config);

				$this->Flash->success(__d('queue', 'The requested job has been queued.'));

				return $this->redirect(['action' => 'test']);
			}

			$this->Flash->error(__d('queue', 'The job could not be queued. Please try again.'));
		}

		$this->set(compact('tasks', 'queuedJob'));
	}

}
