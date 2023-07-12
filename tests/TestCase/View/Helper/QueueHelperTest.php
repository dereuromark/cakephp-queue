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

}
