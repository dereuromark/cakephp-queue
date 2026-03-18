<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\View\Helper;

use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use DateInterval;
use Queue\Model\Entity\QueuedJob;
use Queue\View\Helper\QueueHelper;

class QueueHelperTest extends TestCase {

	/**
	 * @var \Queue\View\Helper\QueueHelper
	 */
	protected $QueueHelper;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->QueueHelper = new QueueHelper(new View(null));
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->QueueHelper);
	}

	/**
	 * @return void
	 */
	public function testHasFailed() {
		$queuedJob = new QueuedJob([
			'job_task' => 'Queue.Example',
			'fetched' => '2019',
			'attempts' => 0,
		]);
		$result = $this->QueueHelper->hasFailed($queuedJob);
		$this->assertFalse($result);

		$queuedJob->attempts = 1;
		$queuedJob->failure_message = 'Foo';
		$result = $this->QueueHelper->hasFailed($queuedJob);
		$this->assertFalse($result);

		$queuedJob->attempts = 999;
		$result = $this->QueueHelper->hasFailed($queuedJob);
		$this->assertTrue($result);

		$queuedJob->attempts = 999;
		$queuedJob->failure_message = null;
		$result = $this->QueueHelper->hasFailed($queuedJob);
		$this->assertFalse($result);
	}

	/**
	 * @return void
	 */
	public function testFails() {
		$queuedJob = new QueuedJob([
			'job_task' => 'Queue.Example',
			'attempts' => 0,
		]);
		$result = $this->QueueHelper->attempts($queuedJob);
		$this->assertSame('0x', $result);

		$queuedJob->attempts = 1;
		$result = $this->QueueHelper->attempts($queuedJob);
		$this->assertSame('1/2', $result);

		$queuedJob->attempts = 2;
		$result = $this->QueueHelper->attempts($queuedJob);
		$this->assertSame('2/2', $result);

		$queuedJob->job_task = 'Queue.ExampleInvalid';
		$result = $this->QueueHelper->attempts($queuedJob);
		$this->assertSame('2x', $result);
	}

	/**
	 * @return void
	 */
	public function testFailureStatus() {
		$queuedJob = new QueuedJob([
			'job_task' => 'Queue.Example',
			'fetched' => '2019',
			'attempts' => 0,
		]);
		$result = $this->QueueHelper->failureStatus($queuedJob);
		$this->assertNull($result);

		$queuedJob->attempts = 1;
		$queuedJob->failure_message = 'Foo';
		$result = $this->QueueHelper->failureStatus($queuedJob);
		$this->assertSame(__d('queue', 'Requeued'), $result);

		$queuedJob->failure_message = null;
		$result = $this->QueueHelper->failureStatus($queuedJob);
		$this->assertSame('Restarted', $result);

		$queuedJob->attempts = 999;
		$queuedJob->failure_message = 'Foo';
		$result = $this->QueueHelper->failureStatus($queuedJob);
		$this->assertSame('Aborted', $result);
	}

	/**
	 * @return void
	 */
	public function testSecondsToHumanReadable(): void {
		// Below threshold - no human readable addition
		$this->assertSame('60', $this->QueueHelper->secondsToHumanReadable(60));
		$this->assertSame('3599', $this->QueueHelper->secondsToHumanReadable(3599));

		// Exactly 1 hour
		$this->assertSame('3600 (1h)', $this->QueueHelper->secondsToHumanReadable(3600));

		// 1 hour 30 minutes
		$this->assertSame('5400 (1h 30m)', $this->QueueHelper->secondsToHumanReadable(5400));

		// 2 hours
		$this->assertSame('7200 (2h)', $this->QueueHelper->secondsToHumanReadable(7200));

		// 1 day
		$this->assertSame('86400 (1d)', $this->QueueHelper->secondsToHumanReadable(86400));

		// 30 days (cleanuptimeout default)
		$this->assertSame('2592000 (30d)', $this->QueueHelper->secondsToHumanReadable(2592000));

		// 1 day 2 hours 30 minutes
		$this->assertSame('95400 (1d 2h 30m)', $this->QueueHelper->secondsToHumanReadable(95400));
	}

	/**
	 * @return void
	 */
	public function testDuration(): void {
		// No completed - returns null
		$queuedJob = new QueuedJob([
			'job_task' => 'Queue.Example',
			'fetched' => new DateTime('-1 hour'),
			'completed' => null,
		]);
		$this->assertNull($this->QueueHelper->duration($queuedJob));

		// No fetched - returns null
		$queuedJob = new QueuedJob([
			'job_task' => 'Queue.Example',
			'fetched' => null,
			'completed' => new DateTime(),
		]);
		$this->assertNull($this->QueueHelper->duration($queuedJob));

		// Both present - returns duration
		$queuedJob = new QueuedJob([
			'job_task' => 'Queue.Example',
			'fetched' => new DateTime('-65 seconds'),
			'completed' => new DateTime(),
		]);
		$result = $this->QueueHelper->duration($queuedJob);
		$this->assertSame('1m 5s', $result);
	}

	/**
	 * @return void
	 */
	public function testIsRequeued(): void {
		// Not fetched - not requeued
		$queuedJob = new QueuedJob([
			'job_task' => 'Queue.Example',
			'fetched' => null,
			'attempts' => 2,
			'failure_message' => 'Error',
		]);
		$this->assertFalse($this->QueueHelper->isRequeued($queuedJob));

		// Completed - not requeued
		$queuedJob = new QueuedJob([
			'job_task' => 'Queue.Example',
			'fetched' => new DateTime('-1 hour'),
			'completed' => new DateTime(),
			'attempts' => 2,
			'failure_message' => 'Error',
		]);
		$this->assertFalse($this->QueueHelper->isRequeued($queuedJob));

		// No failure message - not requeued (just running normally)
		$queuedJob = new QueuedJob([
			'job_task' => 'Queue.Example',
			'fetched' => new DateTime('-1 hour'),
			'attempts' => 2,
			'failure_message' => null,
		]);
		$this->assertFalse($this->QueueHelper->isRequeued($queuedJob));

		// Has failure message but within retry limit - IS requeued
		$queuedJob = new QueuedJob([
			'job_task' => 'Queue.Example',
			'fetched' => new DateTime('-1 hour'),
			'attempts' => 1,
			'failure_message' => 'Error',
		]);
		$this->assertTrue($this->QueueHelper->isRequeued($queuedJob));

		// Has failure message but exceeded retry limit - not requeued (failed)
		$queuedJob = new QueuedJob([
			'job_task' => 'Queue.Example',
			'fetched' => new DateTime('-1 hour'),
			'attempts' => 999,
			'failure_message' => 'Error',
		]);
		$this->assertFalse($this->QueueHelper->isRequeued($queuedJob));
	}

	/**
	 * @return void
	 */
	public function testFormatInterval(): void {
		// Less than 1 second
		$interval = new DateInterval('PT0S');
		$this->assertSame('< 1s', $this->QueueHelper->formatInterval($interval));

		// 1 second
		$interval = new DateInterval('PT1S');
		$this->assertSame('1s', $this->QueueHelper->formatInterval($interval));

		// 30 seconds
		$interval = new DateInterval('PT30S');
		$this->assertSame('30s', $this->QueueHelper->formatInterval($interval));

		// 1 minute 5 seconds
		$interval = new DateInterval('PT1M5S');
		$this->assertSame('1m 5s', $this->QueueHelper->formatInterval($interval));

		// 2 hours 30 minutes
		$interval = new DateInterval('PT2H30M');
		$this->assertSame('2h 30m', $this->QueueHelper->formatInterval($interval));

		// 1 day 2 hours 30 minutes 15 seconds
		$interval = new DateInterval('P1DT2H30M15S');
		$this->assertSame('1d 2h 30m 15s', $this->QueueHelper->formatInterval($interval));
	}

}
