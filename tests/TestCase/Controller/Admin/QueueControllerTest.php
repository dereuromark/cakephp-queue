<?php
namespace Queue\Test\TestCase\Controller\Admin;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 */
class QueueControllerTest extends IntegrationTestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = [
		'plugin.queue.queued_jobs',
		'plugin.queue.queue_processes'
	];

	/**
	 * Test index method
	 *
	 * @return void
	 */
	public function testIndex() {
		$this->_needsConnection();

		$this->get(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testReset() {
		$jobsTable = TableRegistry::get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_type' => 'foo',
			'failed' => 1,
		]);
		$jobsTable->saveOrFail($job);

		$this->post(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'reset']);

		$this->assertResponseCode(302);

		$job = $jobsTable->get($job->id);
		$this->assertSame(0, $job->failed);
	}

	/**
	 * @return void
	 */
	public function testHardReset() {
		$jobsTable = TableRegistry::get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_type' => 'foo'
		]);
		$jobsTable->saveOrFail($job);
		$count = $jobsTable->find()->count();
		$this->assertSame(1, $count);

		$this->post(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'hardReset']);

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
		$this->skipIf(strpos($config['driver'], 'Mysql') === false, 'Only Mysql is working yet for this.');
	}

}
