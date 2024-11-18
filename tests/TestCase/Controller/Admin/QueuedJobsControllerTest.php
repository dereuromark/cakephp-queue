<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Controller\Admin;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\IntegrationTestTrait;
use Laminas\Diactoros\UploadedFile;
use Shim\TestSuite\TestCase;

/**
 * @uses \Queue\Controller\Admin\QueuedJobsController
 */
class QueuedJobsControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->loadPlugins(['Queue']);

		$this->disableErrorHandlerMiddleware();
	}

	/**
	 * @var array
	 */
	protected array $fixtures = [
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

		$queuedJobs = $this->getTableLocator()->get('Queue.QueuedJobs');
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

		$queuedJobs = $this->fetchTable('Queue.QueuedJobs');
		/** @var \Queue\Model\Entity\QueuedJob $modifiedJob */
		$modifiedJob = $queuedJobs->get($job->id);
		$this->assertSame(8, $modifiedJob->priority);
	}

	/**
	 * @return void
	 */
	public function testData() {
		$job = $this->createJob(['data' => '{"verbose":true,"count":22,"string":"string"}']);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'data', $job->id]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testDataPost() {
		$job = $this->createJob();

		$data = [
			'data_string' => <<<JSON
{
    "class": "App\\\\Command\\\\RealNotificationCommand",
    "args": [
        "--verbose",
        "-d"
    ]
}
JSON,
		];
		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'data', $job->id], $data);

		$this->assertResponseCode(302);

		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $this->fetchTable('Queue.QueuedJobs')->get($job->id);
		$expected = '{"class":"App\\\\Command\\\\RealNotificationCommand","args":["--verbose","-d"]}';
		$this->assertSame($expected, $job->data);
	}

	/**
	 * Test index method
	 *
	 * @return void
	 */
	public function testStats() {
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

		$this->requestAsJson();
		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'view', $queuedJob->id]);

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
			'file' => new UploadedFile($jsonFile, 1, 0, 'queued-job.json', 'application/json'),
		];

		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'import'], $data);

		$this->assertResponseCode(302);

		$queuedJobs = $this->getTableLocator()->get('Queue.QueuedJobs');
		/** @var \Queue\Model\Entity\QueuedJob $queuedJob */
		$queuedJob = $queuedJobs->find()->orderByDesc('id')->firstOrFail();

		$this->assertSame('Webhook', $queuedJob->job_task);
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
			'job_task' => 'foo',
		];

		$queuedJobs = $this->getTableLocator()->get('Queue.QueuedJobs');
		$queuedJob = $queuedJobs->newEntity($data);
		$queuedJobs->saveOrFail($queuedJob);

		return $queuedJob;
	}

}
