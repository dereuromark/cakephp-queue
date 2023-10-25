<?php

namespace Queue\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\App;
use Cake\Http\Exception\NotFoundException;
use Queue\Queue\AddFromBackendInterface;
use Queue\Queue\AddInterface;
use Queue\Queue\TaskFinder;

/**
 * @property \Queue\Model\Table\QueuedJobsTable $QueuedJobs
 * @property \Queue\Model\Table\QueueProcessesTable $QueueProcesses
 */
#[\AllowDynamicProperties]
class QueueController extends AppController {

	use LoadHelperTrait;

	/**
	 * @var string
	 */
	protected $modelClass = 'Queue.QueuedJobs';

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadHelpers();
	}

	/**
	 * Admin center.
	 * Manage queues from admin backend (without the need to open ssh console window).
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		$this->QueueProcesses = $this->fetchTable('Queue.QueueProcesses');
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
		$tasks = $taskFinder->all();
		$addableTasks = $taskFinder->allAddable(AddFromBackendInterface::class);

		$servers = $this->QueueProcesses->find()->distinct(['server'])->find('list', ['keyField' => 'server', 'valueField' => 'server'])->toArray();
		$this->set(compact('new', 'current', 'data', 'pendingDetails', 'status', 'tasks', 'addableTasks', 'servers'));
	}

	/**
	 * @param string|null $job Deprecated: Use ?task=... query string instead.
	 *   Note: This fails with plugin syntax, so only to be used for project level ones.
	 * @throws \Cake\Http\Exception\NotFoundException
	 * @return \Cake\Http\Response|null
	 */
	public function addJob($job = null) {
		$this->request->allowMethod('post');

		$job = (string)$this->request->getQuery('task') ?: $job;
		if (!$job) {
			throw new NotFoundException();
		}

		/** @var class-string<\Queue\Queue\Task> $className */
		$className = App::className($job, 'Queue/Task', 'Task');
		if (!$className) {
			throw new NotFoundException('Class not found for job `' . $job . '`');
		}

		$object = new $className();
		if ($object instanceof AddInterface) {
			$object->add(null);
		} else {
			$this->QueuedJobs->createJob($job);
		}

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
		$this->QueueProcesses = $this->fetchTable('Queue.QueueProcesses');

		if ($this->request->is('post') && $this->request->getQuery('end')) {
			$pid = (string)$this->request->getQuery('end');
			$this->QueueProcesses->endProcess($pid);

			return $this->redirect(['action' => 'processes']);
		}
		if ($this->request->is('post') && $this->request->getQuery('kill')) {
			$pid = (string)$this->request->getQuery('kill');
			$this->QueueProcesses->terminateProcess($pid);

			return $this->redirect(['action' => 'processes']);
		}

		$processes = $this->QueueProcesses->getProcesses();
		$terminated = $this->QueueProcesses->find()->where(['terminate' => true])->all()->toArray();
		$key = $this->QueueProcesses->buildServerString();

		$this->set(compact('terminated', 'processes', 'key'));
	}

	/**
	 * Mark all failed jobs as ready for re-run.
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function reset() {
		$this->request->allowMethod('post');
		$resetted = $this->QueuedJobs->reset(null, (bool)$this->request->getQuery('full'));

		$message = __d('queue', '{0} jobs reset for re-run', $resetted);
		$this->Flash->success($message);

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * Remove all failed jobs.
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function flush() {
		$this->request->allowMethod('post');
		$count = $this->QueuedJobs->flushFailedJobs();

		$message = __d('queue', '{0} jobs removed', $count);
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
	 * @param array<mixed>|string $default
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
