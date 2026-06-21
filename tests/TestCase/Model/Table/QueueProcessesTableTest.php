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
		$workerkey = '456';
		$id = $this->QueueProcesses->add('123', $workerkey);
		$this->assertNotEmpty($id);

		$this->QueueProcesses->update($workerkey);

		$queueProcess = $this->QueueProcesses->get($id);
		$this->assertFalse($queueProcess->terminate);
	}

	/**
	 * @return void
	 */
	public function testRemove() {
		$workerkey = '456';
		$queueProcessId = $this->QueueProcesses->add('123', $workerkey);
		$this->assertNotEmpty($queueProcessId);

		$this->QueueProcesses->remove($workerkey);

		$result = $this->QueueProcesses->find()->where(['id' => $queueProcessId])->first();
		$this->assertNull($result);
	}

	/**
	 * After dropping the unique `(pid, server)` index, two rows can coexist
	 * with the same PID on the same server. Mostly happens transiently after
	 * a container restart with PID reuse: one stale row + the new live one.
	 * `workerkey` remains the canonical identity (still uniquely indexed).
	 *
	 * @return void
	 */
	public function testAddAllowsDuplicatePidServer() {
		Configure::write('Queue.maxworkers', 5);
		$this->QueueProcesses->deleteAll(['1=1']);

		$firstId = $this->QueueProcesses->add('8', 'first-workerkey');
		$secondId = $this->QueueProcesses->add('8', 'second-workerkey');

		$this->assertNotEmpty($firstId);
		$this->assertNotEmpty($secondId);
		$this->assertNotSame($firstId, $secondId);
		$this->assertSame(2, $this->QueueProcesses->find()->where(['pid' => '8'])->count());
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

	/**
	 * `--force` ignores the heartbeat threshold and wipes every row. Recovery
	 * path for container restarts where the killed worker's row survives with
	 * a recent `modified` timestamp, so the normal heartbeat-based cleanup
	 * considers it "still alive."
	 *
	 * @return void
	 */
	public function testCleanEndedProcessesForceRemovesAllRows() {
		Configure::write('Queue.maxworkers', 5);

		$this->QueueProcesses->deleteAll(['1=1']);
		$this->QueueProcesses->add('111', 'key-111');
		$this->QueueProcesses->add('222', 'key-222');
		$this->assertSame(2, $this->QueueProcesses->find()->count());

		$deleted = $this->QueueProcesses->cleanEndedProcesses(true);

		$this->assertSame(2, $deleted);
		$this->assertSame(0, $this->QueueProcesses->find()->count());
	}

	/**
	 * Container restart scenario: a worker died without cleaning up its
	 * queue_processes row. A new worker starts with the same recycled PID.
	 * The stale row's heartbeat is older than `staleHeartbeatThreshold`, so
	 * `add()` evicts it before inserting — no duplicate-key crash.
	 *
	 * @return void
	 */
	public function testAddEvictsStaleRowOnSamePidServer() {
		Configure::write('Queue.maxworkers', 5);
		$this->QueueProcesses->deleteAll(['1=1']);

		$staleId = $this->QueueProcesses->add('8', 'old-workerkey');
		$this->QueueProcesses->updateAll(
			['modified' => (new DateTime())->subSeconds(180)->toDateTimeString()],
			['id' => $staleId],
		);

		$newId = $this->QueueProcesses->add('8', 'new-workerkey');
		$this->assertNotEmpty($newId);
		$this->assertNotSame($staleId, $newId);

		$survivors = $this->QueueProcesses->find()->where(['pid' => '8'])->all()->toArray();
		$this->assertCount(1, $survivors);
		$this->assertSame('new-workerkey', $survivors[0]->workerkey);
	}

	/**
	 * If the existing row is fresh (recent heartbeat), it represents a real,
	 * live worker — the eviction guard inside `add()` must NOT touch it.
	 * Verifies the threshold check (`modified <` clause) by asserting the
	 * pre-existing row survives a second `add()` on the same (pid, server).
	 *
	 * @return void
	 */
	public function testAddDoesNotEvictRecentRowOnSamePidServer() {
		Configure::write('Queue.maxworkers', 5);
		$this->QueueProcesses->deleteAll(['1=1']);
		$firstId = $this->QueueProcesses->add('8', 'first-workerkey');

		$this->QueueProcesses->add('8', 'second-workerkey');

		$this->assertTrue(
			$this->QueueProcesses->exists(['id' => $firstId]),
			'Recent row should not be evicted by a subsequent add() on the same (pid, server).',
		);
	}

	/**
	 * Operators can tune the threshold for unusual heartbeat intervals.
	 *
	 * @return void
	 */
	public function testAddRespectsCustomStaleHeartbeatThreshold() {
		Configure::write('Queue.maxworkers', 5);
		Configure::write('Queue.staleHeartbeatThreshold', 30);
		$this->QueueProcesses->deleteAll(['1=1']);

		$staleId = $this->QueueProcesses->add('8', 'old-workerkey');
		$this->QueueProcesses->updateAll(
			['modified' => (new DateTime())->subSeconds(60)->toDateTimeString()],
			['id' => $staleId],
		);

		$newId = $this->QueueProcesses->add('8', 'new-workerkey');
		$this->assertNotEmpty($newId);

		Configure::delete('Queue.staleHeartbeatThreshold');
	}

}
