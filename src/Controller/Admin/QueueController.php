<?php
declare(strict_types=1);

namespace Queue\Controller\Admin;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Queue\Queue\AddFromBackendInterface;
use Queue\Queue\AddInterface;
use Queue\Queue\TaskFinder;

/**
 * @property \Queue\Model\Table\QueuedJobsTable $QueuedJobs
 * @property \Queue\Model\Table\QueueProcessesTable $QueueProcesses
 */
class QueueController extends QueueAppController {

	/**
	 * @var string|null
	 */
	protected ?string $defaultTable = 'Queue.QueuedJobs';

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		// Set connection for multi-connection support
		if ($this->activeConnection !== 'default') {
			$this->QueuedJobs->setConnection($this->getActiveConnectionObject());
		}
	}

	/**
	 * Admin center.
	 * Manage queues from admin backend (without the need to open ssh console window).
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		$QueueProcesses = $this->fetchTable('Queue.QueueProcesses');
		if ($this->activeConnection !== 'default') {
			$QueueProcesses->setConnection($this->getActiveConnectionObject());
		}
		$status = $QueueProcesses->status();

		$current = $this->QueuedJobs->getLength();

		// Cap how many pending/scheduled rows we materialise and pass to the
		// view. Without a cap a backlog of thousands of pending jobs makes
		// the dashboard explode: heavy per-row HTML in the response, and
		// DebugKit's Variables panel serialising every entity for its
		// snapshot can OOM the request. Aggregate tile counts on the
		// dashboard keep using DB-side count() queries so they reflect the
		// true totals regardless of the visible-list cap.
		$detailsLimit = (int)Configure::read('Queue.adminDetailsLimit', 200);

		// +1 row past the cap so we can detect truncation without a second
		// count query in the small-backlog case.
		$pendingDetails = $this->QueuedJobs->getPendingStats()->limit($detailsLimit + 1)->toArray();
		$pendingDetailsTruncated = count($pendingDetails) > $detailsLimit;
		if ($pendingDetailsTruncated) {
			array_pop($pendingDetails);
		}

		$new = $this->QueuedJobs->getNewCount();

		$scheduledDetails = $this->QueuedJobs->getScheduledStats()->limit($detailsLimit + 1)->toArray();
		$scheduledDetailsTruncated = count($scheduledDetails) > $detailsLimit;
		if ($scheduledDetailsTruncated) {
			array_pop($scheduledDetails);
		}

		$data = $this->QueuedJobs->getStats();

		$taskFinder = new TaskFinder();
		$tasks = $taskFinder->all();
		$addableTasks = $taskFinder->allAddable(AddFromBackendInterface::class);

		$taskDescriptions = [];
		foreach ($tasks as $task => $className) {
			/** @var \Queue\Queue\Task $taskObject */
			$taskObject = new $className();
			$taskDescriptions[$task] = $taskObject->description();
		}

		$servers = $QueueProcesses->serverList();
		$workers = $status ? $status['workers'] : 0;

		// True totals from DB. When the visible list is truncated we need
		// these for the "showing N of M" hint; when it isn't, they also
		// happen to match $pendingDetails count (small extra query that
		// avoids one branch in the derivation below).
		$totalPending = $pendingDetailsTruncated
			? $this->QueuedJobs->getPendingCount()
			: count($pendingDetails);
		$scheduledJobs = $scheduledDetailsTruncated
			? $this->QueuedJobs->getScheduledCount()
			: count($scheduledDetails);

		$runningJobs = $this->QueuedJobs->find()
			->where([
				'completed IS' => null,
				'fetched IS NOT' => null,
				'failure_message IS' => null,
			])
			->count();
		$failedJobs = $this->QueuedJobs->find()
			->where([
				'completed IS' => null,
				'failure_message IS NOT' => null,
			])
			->count();
		// Pending = total pending minus running and failed (to avoid double counting)
		$pendingJobs = max(0, $totalPending - $runningJobs - $failedJobs);

		$configurations = (array)Configure::read('Queue');

		$this->set(compact(
			'new',
			'current',
			'data',
			'pendingDetails',
			'scheduledDetails',
			'pendingDetailsTruncated',
			'scheduledDetailsTruncated',
			'detailsLimit',
			'totalPending',
			'status',
			'tasks',
			'addableTasks',
			'taskDescriptions',
			'servers',
			'workers',
			'pendingJobs',
			'scheduledJobs',
			'runningJobs',
			'failedJobs',
			'configurations',
		));
	}

	/**
	 * @throws \Cake\Http\Exception\NotFoundException
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function addJob() {
		$this->request->allowMethod('post');

		$job = (string)$this->request->getQuery('task');
		if (!$job) {
			throw new NotFoundException();
		}

		/** @var class-string<\Queue\Queue\Task> $className */
		$className = App::className($job, 'Queue/Task', 'Task');
		if (!$className) {
			throw new NotFoundException('Class not found for job `' . $job . '`');
		}

		$object = new $className();
		// Measure the QueuedJobs count before invoking the task so we can
		// tell whether the task actually queued a job. AddInterface::add()
		// returns void and may silently no-op when required config is
		// missing (e.g. EmailTask without Config.adminEmail). Without this
		// check the controller would falsely report success.
		$before = $this->QueuedJobs->find()->count();
		if ($object instanceof AddInterface) {
			$object->add(null);
		} else {
			$this->QueuedJobs->createJob($job);
		}
		$after = $this->QueuedJobs->find()->count();

		if ($after > $before) {
			$this->Flash->success('Job ' . $job . ' added');
		} else {
			$this->Flash->error(
				'Job ' . $job . ' could not be added — the task did not create a job. '
				. 'Check configuration (e.g. Config.adminEmail for Queue.Email) and server logs.',
			);
		}

		return $this->refererRedirect(['action' => 'index']);
	}

	/**
	 * @param int|null $id
	 *
	 * @throws \Cake\Http\Exception\NotFoundException
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function resetJob(?int $id = null) {
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
	public function removeJob(?int $id = null) {
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
		$QueueProcesses = $this->fetchTable('Queue.QueueProcesses');
		if ($this->activeConnection !== 'default') {
			$QueueProcesses->setConnection($this->getActiveConnectionObject());
		}

		if ($this->request->is('post') && $this->request->getQuery('end')) {
			$pid = (string)$this->request->getQuery('end');
			$QueueProcesses->endProcess($pid);

			return $this->redirect(['action' => 'processes']);
		}
		if ($this->request->is('post') && $this->request->getQuery('kill')) {
			$pid = (string)$this->request->getQuery('kill');
			$QueueProcesses->terminateProcess($pid);

			return $this->redirect(['action' => 'processes']);
		}

		$processes = $QueueProcesses->getProcesses();
		$terminated = $QueueProcesses->find()->where(['terminate' => true])->all()->toArray();
		$key = $QueueProcesses->buildServerString();

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
	protected function refererRedirect(array|string $default) {
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
