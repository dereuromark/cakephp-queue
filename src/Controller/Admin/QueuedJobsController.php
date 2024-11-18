<?php
declare(strict_types=1);

namespace Queue\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\DateTime;
use Cake\View\JsonView;
use Queue\Queue\TaskFinder;
use RuntimeException;

/**
 * @property \Queue\Model\Table\QueuedJobsTable $QueuedJobs
 * @method \Cake\Datasource\ResultSetInterface<\Queue\Model\Entity\QueuedJob> paginate($object = null, array $settings = [])
 * @property \Search\Controller\Component\SearchComponent $Search
 */
class QueuedJobsController extends AppController {

	use LoadHelperTrait;

	/**
	 * @var array<string, mixed>
	 */
	protected array $paginate = [
		'order' => [
			'created' => 'DESC',
		],
	];

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->enableSearch();
		$this->loadHelpers();
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
			$query = $this->QueuedJobs->find('search', search: $this->request->getQuery());
		} else {
			$query = $this->QueuedJobs->find();
		}
		$queuedJobs = $this->paginate($query);

		$this->set(compact('queuedJobs'));

		if (Configure::read('Queue.isSearchEnabled') !== false && Plugin::isLoaded('Search')) {
			$jobTypes = $this->QueuedJobs->find()->where()->find(
				'list',
				keyField: 'job_task',
				valueField: 'job_task',
			)->distinct('job_task')->toArray();
			$this->set(compact('jobTypes'));
		}
	}

	/**
	 * Index method
	 *
	 * @param string|null $jobType
	 *
	 * @throws \Cake\Http\Exception\NotFoundException
	 *
	 * @return void
	 */
	public function stats(?string $jobType = null): void {
		if (!Configure::read('Queue.isStatisticEnabled')) {
			throw new NotFoundException('Not enabled');
		}

		$stats = $this->QueuedJobs->getFullStats($jobType);

		$jobTypes = $this->QueuedJobs->find()->where()->find(
			'list',
			keyField: 'job_task',
			valueField: 'job_task',
		)->distinct('job_task')->toArray();
		$this->set(compact('stats', 'jobTypes'));
	}

	/**
	 * View method
	 *
	 * @param int|null $id Queued Job id.
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function view(?int $id = null) {
		$queuedJob = $this->QueuedJobs->get(
			(int)$id,
			contain: ['WorkerProcesses'],
		);

		if ($this->request->getParam('_ext') && $this->request->getParam('_ext') === 'json' && $this->request->getQuery('download')) {
			$this->response = $this->response->withDownload('queued-job-' . $id . '.json');
		}

		$this->set(compact('queuedJob'));
		$this->viewBuilder()->setOption('serialize', ['queuedJob']);
	}

	/**
	 * @return array<string>
	 */
	public function viewClasses(): array {
		return [JsonView::class];
	}

	/**
	 * @throws \RuntimeException
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function import() {
		if ($this->request->is(['post'])) {
			/** @var \Laminas\Diactoros\UploadedFile|null $file */
			$file = $this->request->getData('file');
			if ($file && $file->getError() == UPLOAD_ERR_OK && $file->getSize() > 0) {
				$content = file_get_contents($file->getStream()->getMetadata('uri'));
				if ($content === false) {
					throw new RuntimeException('Cannot parse file');
				}
				$json = json_decode($content, true);
				if (!$json || empty($json['queuedJob'])) {
					throw new RuntimeException('Invalid JSON content');
				}

				$data = $json['queuedJob'];

				unset($data['id']);
				$data['created'] = new DateTime($data['created']);

				if ($this->request->getData('reset')) {
					$data['fetched'] = null;
					$data['completed'] = null;
					$data['progress'] = null;
					$data['attempts'] = 0;
					$data['failure_message'] = null;
					$data['workerkey'] = null;
					$data['status'] = null;
				}

				if ($data['notbefore']) {
					$data['notbefore'] = new DateTime($data['notbefore']);
				}
				if ($data['fetched']) {
					$data['fetched'] = new DateTime($data['fetched']);
				}
				if ($data['completed']) {
					$data['completed'] = new DateTime($data['completed']);
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
	 *
	 * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
	 */
	public function edit(?int $id = null) {
		$queuedJob = $this->QueuedJobs->get($id);
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
	 *
	 * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
	 */
	public function data(?int $id = null) {
		$this->QueuedJobs->addBehavior('Queue.Jsonable', ['input' => 'json', 'fields' => ['data'], 'map' => ['data_string']]);

		$queuedJob = $this->QueuedJobs->get($id);
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
	 * Delete method
	 *
	 * @param int|null $id Queued Job id.
	 *
	 * @return \Cake\Http\Response|null|void Redirects to index.
	 */
	public function delete(?int $id = null) {
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
	 * @throws \Cake\Http\Exception\NotFoundException
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function execute() {
		if (!Configure::read('debug')) {
			throw new NotFoundException('Only for local development. Security implications if open on deployment.');
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			/** @var array<string, mixed> $data */
			$data = (array)$this->request->getData();
			if (empty($data['command'])) {
				$this->Flash->error('Command is required');

				return null;
			}

			$amount = $data['amount'];
			unset($data['amount']);

			$data['escape'] = (bool)$data['escape'];
			$data['log'] = (bool)$data['log'];

			$data['redirect'] = !$data['log'];
			$data['accepted'] = $data['exit_code'] === '' ? [] : (array)(int)$data['exit_code'];
			unset($data['exit_code']);

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
		$allTasks = $taskFinder->all();
		$tasks = [];
		foreach ($allTasks as $task => $className) {
			if (!str_starts_with($task, 'Queue.')) {
				continue;
			}
			if (!str_ends_with($task, 'Example')) {
				continue;
			}

			$tasks[$task] = $task;
		}

		$queuedJob = $this->QueuedJobs->newEmptyEntity();

		if ($this->request->is(['post', 'patch', 'put'])) {
			$queuedJob = $this->QueuedJobs->patchEntity($queuedJob, $this->request->getData());
			$jobType = $queuedJob->job_task;
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

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function migrate() {
		$taskFinder = new TaskFinder();
		$allTasks = $taskFinder->all();

		$existingTasks = $this->QueuedJobs->find()
			->select(['job_task'])
			->distinct('job_task')
			->disableHydration()
			->find('list', keyField: 'job_task', valueField: 'job_task')
			->toArray();

		$tasks = [];
		foreach ($allTasks as $task => $className) {
			if (!str_starts_with($task, 'Queue.')) {
				continue;
			}

			[$plugin, $name] = explode('.', $task, 2);
			if (!isset($existingTasks[$name])) {
				continue;
			}

			$tasks[$name] = $task;
		}

		if ($this->request->is('post')) {
			$tasksToMigrate = $this->request->getData('tasks');

			$count = 0;
			foreach ($tasksToMigrate as $taskToMigrate => $status) {
				if (!$status) {
					continue;
				}

				$count += $this->QueuedJobs->updateAll(['job_task' => 'Queue.' . $taskToMigrate], ['job_task' => $taskToMigrate]);
			}

			$this->Flash->success('Done: ' . $count);

			return $this->redirect(['action' => 'migrate']);
		}

		$this->set(compact('tasks'));
	}

}
