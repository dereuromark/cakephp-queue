<?php
namespace Queue\Test\TestCase\Controller;

use Cake\Datasource\ConnectionManager;
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
	 * Helper method for skipping tests that need a real connection.
	 *
	 * @return void
	 */
	protected function _needsConnection() {
		$config = ConnectionManager::config('test');
		$this->skipIf(strpos($config['driver'], 'Mysql') === false, 'Only Mysql is working yet for this.');
	}

}
