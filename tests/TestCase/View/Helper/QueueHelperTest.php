<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
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

}
