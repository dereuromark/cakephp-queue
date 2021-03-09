<?php

namespace Queue\Test\TestCase\Controller\Admin;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * @uses \Queue\Controller\Admin\QueuedJobsController
 */
class QueuedJobsControllerTest extends IntegrationTestCase {

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->disableErrorHandlerMiddleware();
	}

	/**
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Queue.QueuedJobs',
		'plugin.Queue.QueueProcesses',
	];

	/**
	 * Test index method
	 *
	 * @return void
	 */
	public function testIndex() {
		$this->createJob();

		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testEdit() {
		$job = $this->createJob();

		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'edit', $job->id]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testDelete() {
		$job = $this->createJob();

		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'delete', $job->id]);

		$this->assertResponseCode(302);

		$queuedJobs = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
		$queuedJob = $queuedJobs->find()->where(['id' => $job->id])->first();
		$this->assertNull($queuedJob);
	}

	/**
	 * @return void
	 */
	public function testEditPost() {
		$job = $this->createJob();

		$data = [
			'priority' => 8,
		];
		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'edit', $job->id], $data);

		$this->assertResponseCode(302);

		$queuedJobs = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
		/** @var \Queue\Model\Entity\QueuedJob $modifiedJob */
		$modifiedJob = $queuedJobs->get($job->id);
		$this->assertSame(8, $modifiedJob->priority);
	}

	/**
	 * @return void
	 */
	public function testData() {
		$job = $this->createJob();

		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'data', $job->id]);

		$this->assertResponseCode(200);
	}

	/**
	 * Test index method
	 *
	 * @return void
	 */
	public function testStats() {
		$this->_needsConnection();

		Configure::write('Queue.isStatisticEnabled', true);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'stats']);

		$this->assertResponseCode(200);
	}

	/**
	 * Test index method
	 *
	 * @return void
	 */
	public function testTest() {
		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'test']);

		$this->assertResponseCode(200);
	}

	/**
	 * Test index method
	 *
	 * @return void
	 */
	public function testIndexSearch() {
		$this->createJob();

		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'index', '?' => ['status' => 'completed']]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testView() {
		$queuedJob = $this->createJob();

		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'view', $queuedJob->id]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testViewJson() {
		$queuedJob = $this->createJob();

		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'view', $queuedJob->id, '_ext' => 'json']);

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

		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'import'], $data);

		$this->assertResponseCode(302);

		$queuedJobs = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
		/** @var \Queue\Model\Entity\QueuedJob $queuedJob */
		$queuedJob = $queuedJobs->find()->orderDesc('id')->firstOrFail();

		$this->assertSame('Webhook', $queuedJob->job_type);
		$this->assertSame('web-hook-102803234', $queuedJob->reference);
	}

	/**
	 * Helper method for skipping tests that need a real connection.
	 *
	 * @return void
	 */
	protected function _needsConnection() {
		$config = ConnectionManager::getConfig('test');
		$skip = strpos($config['driver'], 'Mysql') === false && strpos($config['driver'], 'Postgres') === false;
		$this->skipIf($skip, 'Only Mysql/Postgres is working yet for this.');
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

		$queuedJobs = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
		$queuedJob = $queuedJobs->newEntity($data);
		$queuedJobs->saveOrFail($queuedJob);

		return $queuedJob;
	}

}
