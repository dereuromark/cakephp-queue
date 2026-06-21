<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Controller\Admin;

use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Queue\Model\Table\QueuedJobsTable;
use Shim\TestSuite\TestCase;

/**
 * @uses \Queue\Controller\Admin\QueueProcessesController
 */
class QueueProcessesControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Queue.QueueProcesses',
		'plugin.Queue.QueuedJobs',
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
	 * Test index method
	 *
	 * @return void
	 */
	public function testIndex() {
		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueueProcesses', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

	/**
	 * Test view method
	 *
	 * @return void
	 */
	public function testView() {
		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueueProcesses', 'action' => 'view', 1]);

		$this->assertResponseCode(200);
	}

	/**
	 * Test edit method
	 *
	 * @return void
	 */
	public function testEdit() {
		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueueProcesses', 'action' => 'edit', 1]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testTerminate() {
		/** @var \Queue\Model\Entity\QueueProcess $queueProcess */
		$queueProcess = $this->getTableLocator()->get('Queue.QueueProcesses')->find()->firstOrFail();
		$queueProcess->terminate = false;
		$this->getTableLocator()->get('Queue.QueueProcesses')->saveOrFail($queueProcess);

		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueueProcesses', 'action' => 'terminate', 1]);

		$this->assertResponseCode(302);

		$queueProcess = $this->getTableLocator()->get('Queue.QueueProcesses')->find()->firstOrFail();
		$this->assertTrue($queueProcess->terminate);
	}

	/**
	 * @return void
	 */
	public function testDelete() {
		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueueProcesses', 'action' => 'delete', 1]);

		$this->assertResponseCode(302);

		$count = $this->getTableLocator()->get('Queue.QueueProcesses')->find()->count();
		$this->assertSame(0, $count);
	}

	/**
	 * @return void
	 */
	public function testCleanup() {
		/** @var \Queue\Model\Entity\QueueProcess $queueProcess */
		$queueProcess = $this->getTableLocator()->get('Queue.QueueProcesses')->find()->firstOrFail();
		$queueProcess->modified = new DateTime(time() - 4 * QueuedJobsTable::DAY);
		$this->getTableLocator()->get('Queue.QueueProcesses')->saveOrFail($queueProcess);

		$this->post(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'QueueProcesses', 'action' => 'cleanup']);

		$this->assertResponseCode(302);

		$count = $this->getTableLocator()->get('Queue.QueueProcesses')->find()->count();
		$this->assertSame(0, $count);
	}

}
