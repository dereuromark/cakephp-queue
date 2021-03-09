<?php

namespace Queue\Test\TestCase\Controller\Admin;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * @uses \Queue\Controller\Admin\QueueController
 */
class QueueControllerTest extends IntegrationTestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Queue.QueuedJobs',
		'plugin.Queue.QueueProcesses',
	];

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->disableErrorHandlerMiddleware();
	}

	/**
	 * Test index method
	 *
	 * @return void
	 */
	public function testIndex() {
		$this->_needsConnection();

		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

	/**
	 * Test index method
	 *
	 * @return void
	 */
	public function testProcesses() {
		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'processes']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testProcessesEnd() {
		$queueProcessesTable = TableRegistry::getTableLocator()->get('Queue.QueueProcesses');
		/** @var \Queue\Model\Entity\QueueProcess $queueProcess */
		$queueProcess = $queueProcessesTable->newEntity([
			'pid' => '1234',
			'workerkey' => '123456',
		]);
		$queueProcessesTable->saveOrFail($queueProcess);

		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'processes', '?' => ['end' => $queueProcess->pid]]);

		$this->assertResponseCode(302);

		$queueProcess = $queueProcessesTable->get($queueProcess->id);
		$this->assertTrue($queueProcess->terminate);
	}

	/**
	 * @return void
	 */
	public function testAddJob() {
		$jobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');

		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'addJob', 'Example']);

		$this->assertResponseCode(302);

		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $jobsTable->find()->orderDesc('id')->firstOrFail();
		$this->assertSame('Example', $job->job_type);
	}

	/**
	 * @return void
	 */
	public function testRemoveJob() {
		$jobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_type' => 'foo',
			'failed' => 1,
		]);
		$jobsTable->saveOrFail($job);

		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'removeJob', $job->id]);

		$this->assertResponseCode(302);

		$job = $jobsTable->find()->where(['id' => $job->id])->first();
		$this->assertNull($job);
	}

	/**
	 * @return void
	 */
	public function testResetJob() {
		$jobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_type' => 'foo',
			'failed' => 1,
		]);
		$jobsTable->saveOrFail($job);

		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'resetJob', $job->id]);

		$this->assertResponseCode(302);

		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $jobsTable->find()->where(['id' => $job->id])->firstOrFail();
		$this->assertSame(0, $job->failed);
	}

	/**
	 * @return void
	 */
	public function testResetJobRedirect() {
		$jobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_type' => 'foo',
			'failed' => 1,
		]);
		$jobsTable->saveOrFail($job);

		$query = ['redirect' => '/foo/bar/baz'];
		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'resetJob', $job->id, '?' => $query]);

		$this->assertResponseCode(302);
		$this->assertHeader('Location', 'http://localhost/foo/bar/baz');

		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $jobsTable->find()->where(['id' => $job->id])->firstOrFail();
		$this->assertSame(0, $job->failed);
	}

	/**
	 * @return void
	 */
	public function testResetJobRedirectInvalid() {
		$jobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_type' => 'foo',
			'failed' => 1,
		]);
		$jobsTable->saveOrFail($job);

		$query = ['redirect' => 'http://x.y.z/foo/bar/baz'];
		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'resetJob', $job->id, '?' => $query]);

		$this->assertResponseCode(302);
		$this->assertHeader('Location', 'http://localhost/admin/queue');

		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $jobsTable->find()->where(['id' => $job->id])->firstOrFail();
		$this->assertSame(0, $job->failed);
	}

	/**
	 * @return void
	 */
	public function testResetJobRedirectReferer() {
		$jobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_type' => 'foo',
			'failed' => 1,
		]);
		$jobsTable->saveOrFail($job);

		$this->configRequest([
			'headers' => [
				'referer' => '/foo/bar/baz',
			],
		]);
		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'resetJob', $job->id]);

		$this->assertResponseCode(302);
		$this->assertHeader('Location', 'http://localhost/foo/bar/baz');

		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $jobsTable->find()->where(['id' => $job->id])->firstOrFail();
		$this->assertSame(0, $job->failed);
	}

	/**
	 * @return void
	 */
	public function testReset() {
		$jobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_type' => 'foo',
			'failed' => 1,
		]);
		$jobsTable->saveOrFail($job);

		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'reset']);

		$this->assertResponseCode(302);

		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $jobsTable->get($job->id);
		$this->assertSame(0, $job->failed);
	}

	/**
	 * @return void
	 */
	public function testHardReset() {
		$jobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_type' => 'foo',
		]);
		$jobsTable->saveOrFail($job);
		$count = $jobsTable->find()->count();
		$this->assertSame(1, $count);

		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'hardReset']);

		$this->assertResponseCode(302);

		$count = $jobsTable->find()->count();
		$this->assertSame(0, $count);
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

}
