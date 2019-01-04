<?php
namespace Queue\Test\TestCase\Controller\Admin;

use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Tools\TestSuite\IntegrationTestCase;

class QueueProcessesControllerTest extends IntegrationTestCase {

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->disableErrorHandlerMiddleware();
	}

	/**
	 * @return void
	 */
	public function tearDown()
	{
		parent::tearDown();

		Configure::delete('Queue');
	}

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = [
		'plugin.Queue.QueueProcesses',
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * Test index method
	 *
	 * @return void
	 */
	public function testIndex() {
		Configure::write('Queue.defaultworkertimeout', DAY);

		$this->get(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'QueueProcesses', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

	/**
	 * Test view method
	 *
	 * @return void
	 */
	public function testView() {
		$this->get(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'QueueProcesses', 'action' => 'view', 1]);

		$this->assertResponseCode(200);
	}

	/**
	 * Test edit method
	 *
	 * @return void
	 */
	public function testEdit() {
		$this->get(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'QueueProcesses', 'action' => 'edit', 1]);

		$this->assertResponseCode(200);
	}

	/**
	 * Test delete method
	 *
	 * @return void
	 */
	public function testDelete() {
		$this->post(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'QueueProcesses', 'action' => 'delete', 1]);

		$this->assertResponseCode(302);

		$count = TableRegistry::get('Queue.QueueProcesses')->find()->count();
		$this->assertSame(0, $count);
	}

	/**
	 * @return void
	 */
	public function testCleanup() {
		Configure::write('Queue.defaultworkertimeout', DAY);

		$queueProcess = TableRegistry::get('Queue.QueueProcesses')->find()->firstOrFail();
		$queueProcess->modified = new FrozenTime(time() - 2 * DAY);
		TableRegistry::get('Queue.QueueProcesses')->saveOrFail($queueProcess);

		$this->post(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'QueueProcesses', 'action' => 'cleanup']);

		$this->assertResponseCode(302);

		$count = TableRegistry::get('Queue.QueueProcesses')->find()->count();
		$this->assertSame(0, $count);
	}

}
