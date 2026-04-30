<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Model\Table;

use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Queue\Model\Table\QueueProcessesTable;
use const SIG_DFL;
use const SIGUSR1;

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
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.Queue.QueueProcesses',
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$config = TableRegistry::getTableLocator()->exists('QueueProcesses') ? [] : ['className' => QueueProcessesTable::class];
		$this->QueueProcesses = $this->getTableLocator()->get('QueueProcesses', $config);

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

	/**
	 * @return void
	 */
	public function testWakeUpWorkersSendsSigUsr1() {
		/** @var \Queue\Model\Table\QueueProcessesTable $queuedProcessesTable */
		$queuedProcessesTable = $this->getTableLocator()->get('Queue.QueueProcesses');
		$queuedProcess = $queuedProcessesTable->newEntity([
			'pid' => (string)getmypid(),
			'workerkey' => $queuedProcessesTable->buildServerString(),
			'server' => $queuedProcessesTable->buildServerString(),
		]);
		$queuedProcessesTable->saveOrFail($queuedProcess);

		$gotSignal = false;
		pcntl_signal(SIGUSR1, function () use (&$gotSignal) {
			$gotSignal = true;
		});

		$queuedProcessesTable->wakeUpWorkers();
		pcntl_signal_dispatch();

		$this->assertTrue($gotSignal);
		pcntl_signal(SIGUSR1, SIG_DFL);
	}

	/**
	 * @return void
	 */
	public function testEndProcess() {
		/** @var \Queue\Model\Table\QueueProcessesTable $queuedProcessesTable */
		$queuedProcessesTable = $this->getTableLocator()->get('Queue.QueueProcesses');

		$queuedProcess = $queuedProcessesTable->newEntity([
			'pid' => '1',
			'workerkey' => '123',
		]);
		$queuedProcessesTable->saveOrFail($queuedProcess);

		$queuedProcessesTable->endProcess('1');

		$queuedProcess = $queuedProcessesTable->get($queuedProcess->id);
		$this->assertTrue($queuedProcess->terminate);
	}

	/**
	 * Stale rows (those whose `modified` timestamp is older than the
	 * configured `Queue.defaultRequeueTimeout`) belong to workers that
	 * died without a graceful shutdown. `cleanEndedProcesses()` removes
	 * them; fresh rows are kept.
	 *
	 * @return void
	 */
	public function testCleanEndedProcessesRemovesStaleRowsOnly() {
		Configure::write('Queue.defaultRequeueTimeout', 60);

		// Wipe fixture rows so the test reasons only about its own data.
		$this->QueueProcesses->deleteAll(['1=1']);

		$stale = $this->QueueProcesses->newEntity(['pid' => '111', 'workerkey' => 'stale-key']);
		$this->QueueProcesses->saveOrFail($stale);
		$this->QueueProcesses->updateAll(
			['modified' => (new DateTime())->subSeconds(120)->toDateTimeString()],
			['id' => $stale->id],
		);

		// Fresh: just created, modified == now.
		$fresh = $this->QueueProcesses->newEntity(['pid' => '222', 'workerkey' => 'fresh-key']);
		$this->QueueProcesses->saveOrFail($fresh);

		$deleted = $this->QueueProcesses->cleanEndedProcesses();

		$this->assertSame(1, $deleted);
		$this->assertFalse(
			$this->QueueProcesses->exists(['pid' => '111']),
			'Stale row should have been deleted',
		);
		$this->assertTrue(
			$this->QueueProcesses->exists(['pid' => '222']),
			'Fresh row should be preserved',
		);
	}

	/**
	 * Regression: a worker starting up must sweep stale `queue_processes`
	 * rows BEFORE attempting to register its own. Without this, a few
	 * dead-but-uncleaned rows count toward `Queue.maxworkers` and can
	 * lock out new workers.
	 *
	 * @return void
	 */
	public function testStaleRowsCountTowardMaxWorkersUntilCleaned() {
		Configure::write('Queue.maxworkers', 2);
		Configure::write('Queue.defaultRequeueTimeout', 60);

		$this->QueueProcesses->deleteAll(['1=1']);
		$server = $this->QueueProcesses->buildServerString();

		// Use add() so `server` is populated as a real worker would.
		$staleId1 = $this->QueueProcesses->add('111', 'key-111');
		$staleId2 = $this->QueueProcesses->add('222', 'key-222');
		$this->QueueProcesses->updateAll(
			['modified' => (new DateTime())->subSeconds(120)->toDateTimeString()],
			['id IN' => [$staleId1, $staleId2]],
		);

		// Without cleanup, validateCount would refuse a third worker.
		$this->assertSame(2, $this->QueueProcesses->find()->where(['server' => $server])->count());

		$this->QueueProcesses->cleanEndedProcesses();

		$this->assertSame(0, $this->QueueProcesses->find()->where(['server' => $server])->count());

		// And the third worker can now register.
		$id = $this->QueueProcesses->add('333', 'key-333');
		$this->assertNotEmpty($id);
	}

}
