<?php

declare(strict_types=1);

namespace Queue\Test\TestCase\Model\Entity;

use Cake\TestSuite\TestCase;
use Queue\Model\Entity\QueuedJob;
use Queue\Model\Entity\QueueProcess;

class MassAssignmentTest extends TestCase {

	public function testQueuedJobProtectsId(): void {
		$job = new QueuedJob(['id' => 42, 'job_task' => 'Example'], ['guard' => true]);
		$this->assertNull($job->get('id'));
		$this->assertSame('Example', $job->get('job_task'));
	}

	public function testQueueProcessProtectsId(): void {
		$process = new QueueProcess(['id' => 42, 'workerkey' => 'example'], ['guard' => true]);
		$this->assertNull($process->get('id'));
		$this->assertSame('example', $process->get('workerkey'));
	}

}
