<?php

namespace Queue\Test\TestCase\Model\Table;

use Cake\Core\Configure;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Queue\Model\Table\QueueProcessesTable;

/**
 * Queue\Model\Table\QueueProcessesTable Test Case
 */
class QueueProcessesTableTest extends TestCase {

	/**
	 * Test subject
	 *
	 * @var \Queue\Model\Table\QueueProcessesTable
	 */
	protected $QueueProcesses;

	/**
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Queue.QueueProcesses',
	];

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$config = TableRegistry::exists('QueueProcesses') ? [] : ['className' => QueueProcessesTable::class];
		$this->QueueProcesses = TableRegistry::getTableLocator()->get('QueueProcesses', $config);

		Configure::delete('Queue.maxworkers');
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		unset($this->QueueProcesses);

		parent::tearDown();

		Configure::delete('Queue.maxworkers');
	}

	/**
	 * @return void
	 */
	public function testAdd() {
		$pid = '123';
		$id = $this->QueueProcesses->add($pid, '456');
		$this->assertNotEmpty($id);

		$queueProcess = $this->QueueProcesses->get($id);
		$this->assertSame($pid, $queueProcess->pid);

		$this->assertFalse($queueProcess->terminate);
		$this->assertNotEmpty($queueProcess->server);
		$this->assertNotEmpty($queueProcess->workerkey);
	}

	/**
	 * @return void
	 */
	public function testAddMaxCount() {
		Configure::write('Queue.maxworkers', 2);

		$pid = '123';
		$id = $this->QueueProcesses->add($pid, '123123');
		$this->assertNotEmpty($id);

		$pid = '234';
		$id = $this->QueueProcesses->add($pid, '234234');
		$this->assertNotEmpty($id);

		$this->expectException(PersistenceFailedException::class);
		$pid = '345';
		$this->QueueProcesses->add($pid, '345345');
	}

	/**
	 * @return void
	 */
	public function testUpdate() {
		$pid = '123';
		$id = $this->QueueProcesses->add($pid, '456');
		$this->assertNotEmpty($id);

		$this->QueueProcesses->update($pid);

		$queueProcess = $this->QueueProcesses->get($id);
		$this->assertFalse($queueProcess->terminate);
	}

	/**
	 * @return void
	 */
	public function testRemove() {
		$pid = '123';
		$queueProcessId = $this->QueueProcesses->add($pid, '456');
		$this->assertNotEmpty($queueProcessId);

		$this->QueueProcesses->remove($pid);

		$result = $this->QueueProcesses->find()->where(['id' => $queueProcessId])->first();
		$this->assertNull($result);
	}

}
