<?php

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
			'job_type' => 'Example',
			'fetched' => '2019',
			'failed' => 0,
		]);
		$result = $this->QueueHelper->hasFailed($queuedJob);
		$this->assertFalse($result);

		$queuedJob->failed = 1;
		$queuedJob->failure_message = 'Foo';
		$result = $this->QueueHelper->hasFailed($queuedJob);
		$this->assertFalse($result);

		$queuedJob->failed = 999;
		$result = $this->QueueHelper->hasFailed($queuedJob);
		$this->assertTrue($result);

		$queuedJob->failed = 999;
		$queuedJob->failure_message = null;
		$result = $this->QueueHelper->hasFailed($queuedJob);
		$this->assertFalse($result);
	}

	/**
	 * @return void
	 */
	public function testFails() {
		$queuedJob = new QueuedJob([
			'job_type' => 'Example',
			'failed' => 0,
		]);
		$result = $this->QueueHelper->fails($queuedJob);
		$this->assertSame('0x', $result);

		$queuedJob->failed = 1;
		$result = $this->QueueHelper->fails($queuedJob);
		$this->assertSame('1/2', $result);

		$queuedJob->failed = 2;
		$result = $this->QueueHelper->fails($queuedJob);
		$this->assertSame('2/2', $result);

		$queuedJob->job_type = 'ExampleInvalid';
		$result = $this->QueueHelper->fails($queuedJob);
		$this->assertSame('2x', $result);
	}

	/**
	 * @return void
	 */
	public function testFailureStatus() {
		$queuedJob = new QueuedJob([
			'job_type' => 'Example',
			'fetched' => '2019',
			'failed' => 0,
		]);
		$result = $this->QueueHelper->failureStatus($queuedJob);
		$this->assertNull($result);

		$queuedJob->failed = 1;
		$queuedJob->failure_message = 'Foo';
		$result = $this->QueueHelper->failureStatus($queuedJob);
		$this->assertSame(__d('queue', 'Requeued'), $result);

		$queuedJob->failure_message = null;
		$result = $this->QueueHelper->failureStatus($queuedJob);
		$this->assertSame('Restarted', $result);

		$queuedJob->failed = 999;
		$queuedJob->failure_message = 'Foo';
		$result = $this->QueueHelper->failureStatus($queuedJob);
		$this->assertSame('Aborted', $result);
	}

}
