<?php
namespace Queue\Test\TestCase\Controller\Admin;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

class QueuedJobsControllerTest extends IntegrationTestCase {

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->disableErrorHandlerMiddleware();
	}

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = [
		'plugin.queue.QueuedJobs',
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
	 * @return void
	 */
	public function testView() {
		$queuedJob = $this->createJob();

		$this->get(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'view', $queuedJob->id]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testViewJson() {
		$queuedJob = $this->createJob();

		$this->get(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'view', $queuedJob->id, '_ext' => 'json']);

		$this->assertResponseCode(200);

		$content = (string)$this->_response->getBody();
		$json = json_decode($content, true);
		$this->assertNotEmpty($json);
	}

	/**
	 * Test view method
	 *
	 * @return void
	 */
	public function testImport() {
		$jsonFile = TESTS . 'test_files' . DS . 'queued-job.json';

		$data = [
			'file' => [
				'size' => 1,
				'error' => 0,
				'tmp_name' => $jsonFile,
			],
		];

		$this->post(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'import'], $data);

		$this->assertResponseCode(302);

		$queuedJobs = TableRegistry::get('Queue.QueuedJobs');
		/** @var \Queue\Model\Entity\QueuedJob $queuedJob */
		$queuedJob = $queuedJobs->find()->orderDesc('id')->firstOrFail();

		$this->assertSame('Webhook', $queuedJob->job_type);
		$this->assertSame('web-hook-102803234', $queuedJob->reference);
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
