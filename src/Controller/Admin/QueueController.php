<?php

namespace Queue\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\App;
use Cake\Http\Exception\NotFoundException;
use Queue\Queue\TaskFinder;

/**
 * @property \Queue\Model\Table\QueuedJobsTable $QueuedJobs
 * @property \Queue\Model\Table\QueueProcessesTable $QueueProcesses
 */
class QueueController extends AppController {

	/**
	 * @var string
	 */
	protected $modelClass = 'Queue.QueuedJobs';

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->viewBuilder()->setHelpers(['Tools.Time', 'Tools.Format', 'Tools.Text', 'Shim.Configure']);
	}

	/**
	 * Admin center.
	 * Manage queues from admin backend (without the need to open ssh console window).
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		$this->loadModel('Queue.QueueProcesses');
		$status = $this->QueueProcesses->status();

		$current = $this->QueuedJobs->getLength();
		$pendingDetails = $this->QueuedJobs->getPendingStats();
		$new = 0;
		foreach ($pendingDetails as $pendingDetail) {
			if ($pendingDetail['fetched'] || $pendingDetail['failed']) {
				continue;
			}
			$new++;
		}

		$data = $this->QueuedJobs->getStats();

		$taskFinder = new TaskFinder();
		$tasks = $taskFinder->allAppAndPluginTasks();

		$servers = $this->QueueProcesses->find()->distinct(['server'])->find('list', ['keyField' => 'server', 'valueField' => 'server'])->toArray();
		$this->set(compact('new', 'current', 'data', 'pendingDetails', 'status', 'tasks', 'servers'));
	}

	/**
	 * @param string|null $job
	 * @throws \Cake\Http\Exception\NotFoundException
	 * @return \Cake\Http\Response|null
	 */
	public function addJob($job = null) {
		$this->request->allowMethod('post');
		if (!$job) {
			throw new NotFoundException();
		}

		$className = App::className('Queue.Queue' . $job, 'Shell/Task', 'Task');
		if (!$className) {
			throw new NotFoundException('Class not found for job `' . $job . '`');
		}

		if (method_exists($className, 'init')) {
			$className::init();
		}

		$this->QueuedJobs->createJob($job);

		$this->Flash->success('Job ' . $job . ' added');

		return $this->refererRedirect(['action' => 'index']);
	}

	/**
	 * @param int|null $id
	 * @throws \Cake\Http\Exception\NotFoundException
	 * @return \Cake\Http\Response|null
	 */
	public function resetJob($id = null) {
		$this->request->allowMethod('post');
		if (!$id) {
			throw new NotFoundException();
		}

		$this->QueuedJobs->reset($id);

		$this->Flash->success('Job # ' . $id . ' re-added');

		return $this->refererRedirect($this->referer(['action' => 'index'], true));
	}

	/**
	 * @param int|null $id
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function removeJob($id = null) {
		$this->request->allowMethod('post');
		$queuedJob = $this->QueuedJobs->get($id);

		$this->QueuedJobs->delete($queuedJob);

		$this->Flash->success('Job # ' . $id . ' deleted');

		return $this->refererRedirect(['action' => 'index']);
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function processes() {
		$processes = $this->QueuedJobs->getProcesses();

		if ($this->request->is('post') && $this->request->getQuery('end')) {
			$pid = (int)$this->request->getQuery('end');
			$this->QueuedJobs->endProcess($pid);

			return $this->redirect(['action' => 'processes']);
		}
		if ($this->request->is('post') && $this->request->getQuery('kill')) {
			$pid = (int)$this->request->getQuery('kill');
			$this->QueuedJobs->terminateProcess($pid);

			return $this->redirect(['action' => 'processes']);
		}

		$this->loadModel('Queue.QueueProcesses');
		$terminated = $this->QueueProcesses->find()->where(['terminate' => true])->all()->toArray();

		$this->set(compact('terminated', 'processes'));
	}

	/**
	 * Mark all failed jobs as ready for re-run.
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function reset() {
		$this->request->allowMethod('post');
		$this->QueuedJobs->reset(null, (bool)$this->request->getQuery('full'));

		$message = __d('queue', 'OK');
		$this->Flash->success($message);

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * Truncate the queue list / table.
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function hardReset() {
		$this->request->allowMethod('post');
		$this->QueuedJobs->truncate();

		$message = __d('queue', 'OK');
		$this->Flash->success($message);

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * @param string|array $default
	 *
	 * @return \Cake\Http\Response|null
	 */
	protected function refererRedirect($default) {
		$url = $this->request->getQuery('redirect');
		if (is_array($url)) {
			throw new NotFoundException('Invalid array in query string');
		}
		if ($url && (mb_substr($url, 0, 1) !== '/' || mb_substr($url, 0, 2) === '//')) {
			$url = null;
		}

		return $this->redirect($url ?: $default);
	}

}
