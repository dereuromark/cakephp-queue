<?php
declare(strict_types=1);

namespace Queue\Event;

use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Queue\Model\Entity\QueuedJob;

/**
 * Event listener that bridges Queue.Job.* events to CakeSentry.Queue.* events.
 *
 * This enables integration with lordsimal/cakephp-sentry for queue monitoring
 * without the Queue plugin depending on the Sentry SDK.
 *
 * Usage:
 * ```php
 * // In Application::bootstrap() or a plugin bootstrap
 * EventManager::instance()->on(new \Queue\Event\CakeSentryEventBridge());
 * ```
 *
 * @see https://github.com/LordSimal/cakephp-sentry
 */
class CakeSentryEventBridge implements EventListenerInterface {

	/**
	 * @var float|null Start time for execution time calculation
	 */
	protected ?float $startTime = null;

	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Queue.Job.created' => 'handleCreated',
			'Queue.Job.started' => 'handleStarted',
			'Queue.Job.completed' => 'handleCompleted',
			'Queue.Job.failed' => 'handleFailed',
		];
	}

	/**
	 * Handle job created event - dispatches CakeSentry.Queue.enqueue
	 *
	 * @param \Cake\Event\EventInterface $event
	 *
	 * @return void
	 */
	public function handleCreated(EventInterface $event): void {
		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $event->getData('job');

		$sentryEvent = new Event('CakeSentry.Queue.enqueue', $this, [
			'class' => $job->job_task,
			'id' => (string)$job->id,
			'queue' => $job->job_task,
			'data' => $job->data ?? [],
		]);
		EventManager::instance()->dispatch($sentryEvent);
	}

	/**
	 * Handle job started event - dispatches CakeSentry.Queue.beforeExecute
	 *
	 * @param \Cake\Event\EventInterface $event
	 *
	 * @return void
	 */
	public function handleStarted(EventInterface $event): void {
		$this->startTime = microtime(true);

		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $event->getData('job');
		$jobData = is_array($job->data) ? $job->data : [];

		$sentryEvent = new Event('CakeSentry.Queue.beforeExecute', $this, [
			'class' => $job->job_task,
			'sentry_trace' => $jobData['_sentry_trace'] ?? '',
			'sentry_baggage' => $jobData['_sentry_baggage'] ?? '',
		]);
		EventManager::instance()->dispatch($sentryEvent);
	}

	/**
	 * Handle job completed event - dispatches CakeSentry.Queue.afterExecute
	 *
	 * @param \Cake\Event\EventInterface $event
	 *
	 * @return void
	 */
	public function handleCompleted(EventInterface $event): void {
		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $event->getData('job');

		$sentryEvent = new Event('CakeSentry.Queue.afterExecute', $this, $this->buildAfterExecuteData($job));
		EventManager::instance()->dispatch($sentryEvent);
	}

	/**
	 * Handle job failed event - dispatches CakeSentry.Queue.afterExecute with exception
	 *
	 * @param \Cake\Event\EventInterface $event
	 *
	 * @return void
	 */
	public function handleFailed(EventInterface $event): void {
		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $event->getData('job');
		$exception = $event->getData('exception');

		$data = $this->buildAfterExecuteData($job);
		if ($exception !== null) {
			$data['exception'] = $exception;
		}

		$sentryEvent = new Event('CakeSentry.Queue.afterExecute', $this, $data);
		EventManager::instance()->dispatch($sentryEvent);
	}

	/**
	 * Build common data for afterExecute event.
	 *
	 * @param \Queue\Model\Entity\QueuedJob $job
	 *
	 * @return array<string, mixed>
	 */
	protected function buildAfterExecuteData(QueuedJob $job): array {
		$executionTime = 0;
		if ($this->startTime !== null) {
			$executionTime = (int)((microtime(true) - $this->startTime) * 1000);
			$this->startTime = null;
		}

		return [
			'id' => (string)$job->id,
			'queue' => $job->job_task,
			'data' => $job->data ?? [],
			'execution_time' => $executionTime,
			'retry_count' => $job->attempts,
		];
	}

}
