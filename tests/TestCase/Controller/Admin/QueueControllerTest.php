<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Controller\Admin;

use Cake\Datasource\ConnectionManager;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Queue\Controller\Admin\QueueController;
use Shim\TestSuite\TestCase;
use Tools\I18n\DateTime as ToolsDateTime;

/**
 * @uses \Queue\Controller\Admin\QueueController
 */
class QueueControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Queue.QueuedJobs',
		'plugin.Queue.QueueProcesses',
	];

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->loadPlugins(['Queue']);

		$this->disableErrorHandlerMiddleware();
	}

	/**
	 * @return void
	 */
	public function testLoadHelpers(): void {
		$controller = new QueueController(new ServerRequest(['url' => 'controller/posts/index']));
		$this->invokeMethod($controller, 'loadHelpers');

		$view = $controller->createView();
		$engine = $view->Time->getConfig('engine');
		$this->assertTrue(in_array($engine, [DateTime::class, ToolsDateTime::class], true));
	}

	/**
	 * Test index method
	 *
	 * @return void
	 */
	public function testIndex() {
		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

	/**
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
		$queueProcessesTable = $this->getTableLocator()->get('Queue.QueueProcesses');
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
		$jobsTable = $this->getTableLocator()->get('Queue.QueuedJobs');

		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'addJob', '?' => ['task' => 'Queue.Example']]);

		$this->assertResponseCode(302);

		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $jobsTable->find()->orderByDesc('id')->firstOrFail();
		$this->assertSame('Queue.Example', $job->job_task);
	}

	/**
	 * @return void
	 */
	public function testRemoveJob() {
		$jobsTable = $this->getTableLocator()->get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_task' => 'foo',
			'attempts' => 1,
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
		$jobsTable = $this->getTableLocator()->get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_task' => 'foo',
			'attempts' => 1,
		]);
		$jobsTable->saveOrFail($job);

		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'resetJob', $job->id]);

		$this->assertResponseCode(302);

		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $jobsTable->find()->where(['id' => $job->id])->firstOrFail();
		$this->assertSame(0, $job->attempts);
	}

	/**
	 * @return void
	 */
	public function testResetJobRedirect() {
		$jobsTable = $this->getTableLocator()->get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_task' => 'foo',
			'attempts' => 1,
		]);
		$jobsTable->saveOrFail($job);

		$query = ['redirect' => '/foo/bar/baz'];
		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'resetJob', $job->id, '?' => $query]);

		$this->assertResponseCode(302);
		$this->assertHeader('Location', 'http://localhost/foo/bar/baz');

		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $jobsTable->find()->where(['id' => $job->id])->firstOrFail();
		$this->assertSame(0, $job->attempts);
	}

	/**
	 * @return void
	 */
	public function testResetJobRedirectInvalid() {
		$jobsTable = $this->getTableLocator()->get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_task' => 'foo',
			'attempts' => 1,
		]);
		$jobsTable->saveOrFail($job);

		$query = ['redirect' => 'http://x.y.z/foo/bar/baz'];
		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'resetJob', $job->id, '?' => $query]);

		$this->assertResponseCode(302);
		$this->assertHeader('Location', 'http://localhost/admin/queue');

		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $jobsTable->find()->where(['id' => $job->id])->firstOrFail();
		$this->assertSame(0, $job->attempts);
	}

	/**
	 * @return void
	 */
	public function testResetJobRedirectReferer() {
		$jobsTable = $this->getTableLocator()->get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_task' => 'foo',
			'attempts' => 1,
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
		$this->assertSame(0, $job->attempts);
	}

	/**
	 * @return void
	 */
	public function testReset() {
		$jobsTable = $this->getTableLocator()->get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_task' => 'foo',
			'attempts' => 1,
		]);
		$jobsTable->saveOrFail($job);

		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'reset']);

		$this->assertResponseCode(302);

		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $jobsTable->get($job->id);
		$this->assertSame(0, $job->attempts);
	}

	/**
	 * @return void
	 */
	public function testFlush() {
		$jobsTable = $this->getTableLocator()->get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_task' => 'foo',
			'attempts' => 1,
			'fetched' => (new DateTime())->subHours(1),
		]);
		$jobsTable->saveOrFail($job);

		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'flush']);

		$this->assertResponseCode(302);

		/** @var \Queue\Model\Entity\QueuedJob|null $job */
		$job = $jobsTable->find()->where(['id' => $job->id])->first();
		$this->assertNull($job);
	}

	/**
	 * @return void
	 */
	public function testHardReset() {
		$jobsTable = $this->getTableLocator()->get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_task' => 'foo',
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
