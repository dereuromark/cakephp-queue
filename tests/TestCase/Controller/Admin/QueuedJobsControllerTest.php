<?php
namespace Queue\Test\TestCase\Controller\Admin;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

class QueuedJobsControllerTest extends IntegrationTestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = [
		'plugin.queue.queued_jobs',
	];

	/**
	 * Test index method
	 *
	 * @return void
	 */
	public function testIndex() {
		$this->createJob();

		$this->get(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

	/**
	 * Test view method
	 *
	 * @return void
	 */
	public function testView() {
		$queuedJob = $this->createJob();

		$this->get(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'view', $queuedJob->id]);

		$this->assertResponseCode(200);
	}

	/**
	 * @param array $data
	 *
	 * @return \Queue\Model\Entity\QueuedJob
	 */
	protected function createJob(array $data = []) {
		$data += [
			'job_type' => 'foo',
		];

		$queuedJobs = TableRegistry::get('Queue.QueuedJobs');
		$queuedJob = $queuedJobs->newEntity($data);
		$queuedJobs->saveOrFail($queuedJob);

		return $queuedJob;
	}

}
