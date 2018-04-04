<?php
namespace Queue\Test\TestCase\Model\Table;

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
	public $QueueProcesses;

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = [
		'plugin.queue.queue_processes'
	];

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$config = TableRegistry::exists('QueueProcesses') ? [] : ['className' => QueueProcessesTable::class];
		$this->QueueProcesses = TableRegistry::get('QueueProcesses', $config);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->QueueProcesses);

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testAdd() {
		$pid = '123';
		$id = $this->QueueProcesses->add($pid);
		$this->assertNotEmpty($id);
	}

	/**
	 * @return void
	 */
	public function testUpdate() {
		$pid = '123';
		$id = $this->QueueProcesses->add($pid);
		$this->assertNotEmpty($id);

		$this->QueueProcesses->update($pid);
	}

	/**
	 * @return void
	 */
	public function testRemove() {
		$pid = '123';
		$queueProcess = $this->QueueProcesses->newEntity([
			'pid' => $pid
		]);
		$this->QueueProcesses->saveOrFail($queueProcess);

		$this->QueueProcesses->remove($pid);

		$result = $this->QueueProcesses->find()->where(['id' => $queueProcess->id])->first();
		$this->assertNull($result);
	}

}
