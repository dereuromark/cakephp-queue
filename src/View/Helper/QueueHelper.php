<?php
declare(strict_types=1);

namespace Queue\View\Helper;

use Cake\View\Helper;
use Queue\Model\Entity\QueuedJob;
use Queue\Queue\Config;
use Queue\Queue\TaskFinder;

/**
 * @property \Queue\Model\Table\QueuedJobsTable $QueuedJobs
 */
class QueueHelper extends Helper {

	/**
	 * @var array<string, array<string, mixed>>
	 */
	protected array $taskConfig = [];

	/**
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 *
	 * @return bool
	 */
	public function hasFailed(QueuedJob $queuedJob): bool {
		if ($queuedJob->completed || !$queuedJob->fetched || !$queuedJob->attempts) {
			return false;
		}

		// Restarted
		if (!$queuedJob->failure_message) {
			return false;
		}

		// Requeued
		$taskConfig = $this->taskConfig($queuedJob->job_task);
		if ($taskConfig && $queuedJob->attempts <= $taskConfig['retries']) {
			return false;
		}

		return true;
	}

	/**
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 *
	 * @return string|null
	 */
	public function attempts(QueuedJob $queuedJob): ?string {
		if ($queuedJob->attempts < 1) {
			return '0x';
		}

		$taskConfig = $this->taskConfig($queuedJob->job_task);
		if ($taskConfig) {
			$maxFails = $taskConfig['retries'] + 1;

			return $queuedJob->attempts . '/' . $maxFails;
		}

		return $queuedJob->attempts . 'x';
	}

	/**
	 * Returns failure status (message) if applicable.
	 *
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 *
	 * @return string|null
	 */
	public function failureStatus(QueuedJob $queuedJob): ?string {
		if ($queuedJob->completed || !$queuedJob->fetched || !$queuedJob->attempts) {
			return null;
		}

		if (!$queuedJob->failure_message) {
			return __d('queue', 'Restarted');
		}

		$taskConfig = $this->taskConfig($queuedJob->job_task);
		if ($taskConfig && $queuedJob->attempts <= $taskConfig['retries']) {
			return __d('queue', 'Requeued');
		}

		return __d('queue', 'Aborted');
	}

	/**
	 * @param string $jobTask
	 *
	 * @return array<string, mixed>
	 */
	protected function taskConfig(string $jobTask): array {
		if (!$this->taskConfig) {
			$tasks = (new TaskFinder())->all();
			$this->taskConfig = Config::taskConfig($tasks);
		}

		return $this->taskConfig[$jobTask] ?? [];
	}

}
