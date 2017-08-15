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
	 * Test initialize method
	 *
	 * @return void
	 */
	public function testInitialize() {
		$this->markTestIncomplete('Not implemented yet.');
	}

	/**
	 * Test validationDefault method
	 *
	 * @return void
	 */
	public function testValidationDefault() {
		$this->markTestIncomplete('Not implemented yet.');
	}

}
