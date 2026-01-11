<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Event;

use Cake\Event\Event;
use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;
use Queue\Event\CakeSentryEventBridge;
use Queue\Model\Entity\QueuedJob;
use RuntimeException;

class CakeSentryEventBridgeTest extends TestCase {

	/**
	 * @var \Queue\Event\CakeSentryEventBridge
	 */
	protected CakeSentryEventBridge $bridge;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->bridge = new CakeSentryEventBridge();
	}

	/**
	 * @return void
	 */
	public function testImplementedEvents(): void {
		$events = $this->bridge->implementedEvents();

		$this->assertArrayHasKey('Queue.Job.created', $events);
		$this->assertArrayHasKey('Queue.Job.started', $events);
		$this->assertArrayHasKey('Queue.Job.completed', $events);
		$this->assertArrayHasKey('Queue.Job.failed', $events);
	}

	/**
	 * @return void
	 */
	public function testHandleCreatedDispatchesCakeSentryEvent(): void {
		$eventList = new EventList();
		EventManager::instance()->setEventList($eventList);
		EventManager::instance()->on($this->bridge);

		$job = new QueuedJob([
			'id' => 123,
			'job_task' => 'Queue.Example',
			'data' => ['test' => 'data'],
		]);

		$event = new Event('Queue.Job.created', null, ['job' => $job]);
		EventManager::instance()->dispatch($event);

		$this->assertEventFired('CakeSentry.Queue.enqueue');
	}

	/**
	 * @return void
	 */
	public function testHandleStartedDispatchesCakeSentryEvent(): void {
		$eventList = new EventList();
		EventManager::instance()->setEventList($eventList);
		EventManager::instance()->on($this->bridge);

		$job = new QueuedJob([
			'id' => 123,
			'job_task' => 'Queue.Example',
			'data' => ['test' => 'data'],
		]);

		$event = new Event('Queue.Job.started', null, ['job' => $job]);
		EventManager::instance()->dispatch($event);

		$this->assertEventFired('CakeSentry.Queue.beforeExecute');
	}

	/**
	 * @return void
	 */
	public function testHandleCompletedDispatchesCakeSentryEvent(): void {
		$eventList = new EventList();
		EventManager::instance()->setEventList($eventList);
		EventManager::instance()->on($this->bridge);

		$job = new QueuedJob([
			'id' => 123,
			'job_task' => 'Queue.Example',
			'data' => ['test' => 'data'],
			'attempts' => 1,
		]);

		$event = new Event('Queue.Job.completed', null, ['job' => $job]);
		EventManager::instance()->dispatch($event);

		$this->assertEventFired('CakeSentry.Queue.afterExecute');
	}

	/**
	 * @return void
	 */
	public function testHandleFailedDispatchesCakeSentryEvent(): void {
		$eventList = new EventList();
		EventManager::instance()->setEventList($eventList);
		EventManager::instance()->on($this->bridge);

		$job = new QueuedJob([
			'id' => 123,
			'job_task' => 'Queue.Example',
			'data' => ['test' => 'data'],
			'attempts' => 1,
		]);
		$exception = new RuntimeException('Test failure');

		$event = new Event('Queue.Job.failed', null, [
			'job' => $job,
			'failureMessage' => 'Test failure',
			'exception' => $exception,
		]);
		EventManager::instance()->dispatch($event);

		$this->assertEventFired('CakeSentry.Queue.afterExecute');
	}

	/**
	 * @return void
	 */
	public function testSentryTraceHeadersArePassedThrough(): void {
		$eventList = new EventList();
		EventManager::instance()->setEventList($eventList);
		EventManager::instance()->on($this->bridge);

		$job = new QueuedJob([
			'id' => 123,
			'job_task' => 'Queue.Example',
			'data' => [
				'_sentry_trace' => 'test-trace-id',
				'_sentry_baggage' => 'test-baggage',
				'other_data' => 'value',
			],
		]);

		$event = new Event('Queue.Job.started', null, ['job' => $job]);
		EventManager::instance()->dispatch($event);

		$this->assertEventFired('CakeSentry.Queue.beforeExecute');
	}

}
