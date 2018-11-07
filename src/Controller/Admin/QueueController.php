<?php

namespace Queue\Controller\Admin;

use App\Controller\AppController;
use Cake\Event\Event;
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
	public $modelClass = 'Queue.QueuedJobs';

	/**
	 * @return void
	 */
	public function initialize() {
		parent::initialize();

		$this->QueuedJobs->initConfig();
	}

	/**
	 * Admin center.
	 * Manage queues from admin backend (without the need to open ssh console window).
	 *
	 * @return \Cake\Http\Response|null
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

		$this->set(compact('new', 'current', 'data', 'pendingDetails', 'status', 'tasks'));
		$this->helpers[] = 'Tools.Format';
		$this->helpers[] = 'Tools.Time';
	}

	/**
	 * @param string|null $job
	 *
	 * @return \Cake\Http\Response
	 *
	 * @throws \Cake\Http\Exception\NotFoundException
	 */
	public function addJob($job = null) {
		if (!$job) {
			throw new NotFoundException();
		}

		$this->QueuedJobs->createJob($job);

		$this->Flash->success('Job ' . $job . ' added');

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * @param string|null $id
	 *
	 * @return \Cake\Http\Response
	 *
	 * @throws \Cake\Http\Exception\NotFoundException
	 */
	public function resetJob($id = null) {
		if (!$id) {
			throw new NotFoundException();
		}

		$this->QueuedJobs->reset($id);

		$this->Flash->success('Job # ' . $id . ' re-added');

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * @param string|null $id
	 *
	 * @return \Cake\Http\Response
	 */
	public function removeJob($id = null) {
		$queuedJob = $this->QueuedJobs->get($id);

		$this->QueuedJobs->delete($queuedJob);

		$this->Flash->success('Job # ' . $id . ' deleted');

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function processes() {
		$processes = $this->QueuedJobs->getProcesses();

		if ($this->request->is('post') && $this->request->getQuery('kill')) {
			$pid = $this->request->getQuery('kill');
			$this->QueuedJobs->terminateProcess($pid);

			return $this->redirect(['action' => 'processes']);
		}

		$this->set(compact('processes'));
	}

	/**
	 * Mark all failed jobs as ready for re-run.
	 *
	 * @return \Cake\Http\Response
	 * @throws \Cake\Http\Exception\MethodNotAllowedException when not posted
	 */
	public function reset() {
		$this->request->allowMethod('post');
		$this->QueuedJobs->reset();

		$message = __d('queue', 'OK');
		$this->Flash->success($message);

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * Truncate the queue list / table.
	 *
	 * @return \Cake\Http\Response
	 * @throws \Cake\Http\Exception\MethodNotAllowedException when not posted
	 */
	public function hardReset() {
		$this->request->allowMethod('post');
		$this->QueuedJobs->truncate();

		$message = __d('queue', 'OK');
		$this->Flash->success($message);

		return $this->redirect(['action' => 'index']);
	}

}
