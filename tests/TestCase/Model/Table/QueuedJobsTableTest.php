<?php
/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */

namespace Queue\Test\TestCase\Model\Table;

use Cake\Datasource\ConnectionManager;
use Cake\I18n\FrozenTime;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Queue\Model\Table\QueuedJobsTable;

/**
 * Queue\Model\Table\QueuedJobsTable Test Case
 */
class QueuedJobsTableTest extends TestCase {

	/**
	 * @var \Queue\Model\Table\QueuedJobsTable
	 */
	protected $QueuedJobs;

	/**
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Queue.QueuedJobs',
		'plugin.Queue.QueueProcesses',
	];

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$config = TableRegistry::exists('QueuedJobs') ? [] : ['className' => QueuedJobsTable::class];
		$this->QueuedJobs = TableRegistry::getTableLocator()->get('QueuedJobs', $config);
	}

	/**
	 * Basic Instance test
	 *
	 * @return void
	 */
	public function testQueueInstance() {
		$this->assertInstanceOf(QueuedJobsTable::class, $this->QueuedJobs);
	}

	/**
	 * Test the basic create and length evaluation functions.
	 *
	 * @return void
	 */
	public function testCreateAndCount() {
		// at first, the queue should contain 0 items.
		$this->assertSame(0, $this->QueuedJobs->getLength());

		// create a job
		$this->assertTrue((bool)$this->QueuedJobs->createJob('test1', [
			'some' => 'random',
			'test' => 'data',
		]));

		// test if queue Length is 1 now.
		$this->assertSame(1, $this->QueuedJobs->getLength());

		//create some more jobs
		$this->assertTrue((bool)$this->QueuedJobs->createJob('test2', [
			'some' => 'random',
			'test' => 'data2',
		]));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('test2', [
			'some' => 'random',
			'test' => 'data3',
		]));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('test3', [
			'some' => 'random',
			'test' => 'data4',
		]));

		//overall queueLength shpould now be 4
		$this->assertSame(4, $this->QueuedJobs->getLength());

		// there should be 1 task of type 'test1', one of type 'test3' and 2 of type 'test2'
		$this->assertSame(1, $this->QueuedJobs->getLength('test1'));
		$this->assertSame(2, $this->QueuedJobs->getLength('test2'));
		$this->assertSame(1, $this->QueuedJobs->getLength('test3'));
	}

	/**
	 * Test the basic create and fetch functions.
	 *
	 * @return void
	 */
	public function testCreateAndFetch() {
		$this->_needsConnection();

		//$capabilities is a list of tasks the worker can run.
		$capabilities = [
			'task1' => [
				'name' => 'task1',
				'timeout' => 100,
				'retries' => 2,
				'rate' => 0,
				'costs' => 0,
				'unique' => false,
			],
		];
		$testData = [
			'x1' => 'y1',
			'x2' => 'y2',
			'x3' => 'y3',
			'x4' => 'y4',
		];

		// start off empty.
		$this->assertSame([], $this->QueuedJobs->find()->toArray());
		// at first, the queue should contain 0 items.
		$this->assertSame(0, $this->QueuedJobs->getLength());
		// there are no jobs, so we cant fetch any.
		$this->assertNull($this->QueuedJobs->requestJob($capabilities));
		// insert one job.
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', $testData));

		// fetch and check the first job.
		$job = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame(1, $job->id);
		$this->assertSame('task1', $job->job_type);
		$this->assertSame(0, $job->failed);
		$this->assertNull($job->completed);
		$this->assertSame($testData, unserialize($job->data));

		// after this job has been fetched, it may not be reassigned.
		$result = $this->QueuedJobs->requestJob($capabilities);
		$this->assertNull($result);

		// queue length is still 1 since the first job did not finish.
		$this->assertSame(1, $this->QueuedJobs->getLength());

		// Now mark Task1 as done
		$this->assertTrue($this->QueuedJobs->markJobDone($job));

		// Should be 0 again.
		$this->assertSame(0, $this->QueuedJobs->getLength());
	}

	/**
	 * Test the delivery of jobs in sequence, skipping fetched but not completed tasks.
	 *
	 * @return void
	 */
	public function testSequence() {
		$this->_needsConnection();

		//$capabilities is a list of tasks the worker can run.
		$capabilities = [
			'task1' => [
				'name' => 'task1',
				'timeout' => 100,
				'retries' => 2,
				'rate' => 0,
				'costs' => 0,
				'unique' => false,
			],
		];
		// at first, the queue should contain 0 items.
		$this->assertSame(0, $this->QueuedJobs->getLength());
		// create some more jobs
		foreach (range(0, 9) as $num) {
			$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', [
				'tasknum' => $num,
			]));
		}
		// 10 jobs in the queue.
		$this->assertSame(10, $this->QueuedJobs->getLength());

		// jobs should be fetched in the original sequence.
		$array = [];
		foreach (range(0, 4) as $num) {
			$this->QueuedJobs->clearKey();
			$array[$num] = $this->QueuedJobs->requestJob($capabilities);
			$jobData = unserialize($array[$num]['data']);
			$this->assertSame($num, $jobData['tasknum']);
		}
		// now mark them as done
		foreach (range(0, 4) as $num) {
			$this->assertTrue($this->QueuedJobs->markJobDone($array[$num]));
			$this->assertSame(9 - $num, $this->QueuedJobs->getLength());
		}

		// jobs should be fetched in the original sequence.
		foreach (range(5, 9) as $num) {
			$job = $this->QueuedJobs->requestJob($capabilities);
			$jobData = unserialize($job->data);
			$this->assertSame($num, $jobData['tasknum']);
			$this->assertTrue($this->QueuedJobs->markJobDone($job));
			$this->assertSame(9 - $num, $this->QueuedJobs->getLength());
		}
	}

	/**
	 * Test creating Jobs to run close to a specified time, and strtotime parsing.
	 * Using toUnixString() function to convert Time object to timestamp, instead of strtotime
	 *
	 * @return null
	 */
	public function testNotBefore() {
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', null, ['notBefore' => '+ 1 Min']));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', null, ['notBefore' => '+ 1 Day']));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', null, ['notBefore' => '2009-07-01 12:00:00']));
		$data = $this->QueuedJobs->find('all')->toArray();
		$this->assertWithinRange((new Time('+ 1 Min'))->toUnixString(), $data[0]['notbefore']->toUnixString(), 60);
		$this->assertWithinRange((new Time('+ 1 Day'))->toUnixString(), $data[1]['notbefore']->toUnixString(), 60);
		$this->assertWithinRange((new Time('2009-07-01 12:00:00'))->toUnixString(), $data[2]['notbefore']->toUnixString(), 60);
	}

	/**
	 * Test Job reordering depending on 'notBefore' field.
	 * Jobs with an expired notbefore field should be executed before any other job without specific timing info.
	 *
	 * @return void
	 */
	public function testNotBeforeOrder() {
		$this->_needsConnection();

		$capabilities = [
			'task1' => [
				'name' => 'task1',
				'timeout' => 100,
				'retries' => 2,
				'rate' => 0,
				'costs' => 0,
				'unique' => false,
			],
			'dummytask' => [
				'name' => 'dummytask',
				'timeout' => 100,
				'retries' => 2,
				'rate' => 0,
				'costs' => 0,
				'unique' => false,
			],
		];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('dummytask'));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('dummytask'));
		// create a task with it's execution target some seconds in the past, so it should jump to the top of the list.
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', ['three'], ['notBefore' => '- 3 Seconds']));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', ['two'], ['notBefore' => '- 5 Seconds']));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', ['one'], ['notBefore' => '- 7 Seconds']));

		// when using requestJob, the jobs we just created should be delivered in this order, NOT the order in which they where created.
		$expected = [
			[
				'name' => 'task1',
				'data' => ['one'],
			],
			[
				'name' => 'task1',
				'data' => ['two'],
			],
			[
				'name' => 'task1',
				'data' => ['three'],
			],
			[
				'name' => 'dummytask',
				'data' => null,
			],
			[
				'name' => 'dummytask',
				'data' => null,
			],
		];

		foreach ($expected as $item) {
			$this->QueuedJobs->clearKey();
			$tmp = $this->QueuedJobs->requestJob($capabilities);

			$this->assertSame($item['name'], $tmp['job_type']);
			$this->assertEquals($item['data'], unserialize($tmp['data']));
		}
	}

	/**
	 * @return void
	 */
	public function testFindQueued() {
		$queued = $this->QueuedJobs->find('queued')->count();
		$this->assertSame(0, $queued);
	}

	/**
	 * Job Rate limiting.
	 * Do not execute jobs of a certain type more often than once every X seconds.
	 *
	 * @return void
	 */
	public function testRateLimit() {
		$this->_needsConnection();

		$capabilities = [
			'task1' => [
				'name' => 'task1',
				'timeout' => 101,
				'retries' => 2,
				'rate' => 2,
				'costs' => 0,
				'unique' => false,
			],
			'dummytask' => [
				'name' => 'dummytask',
				'timeout' => 101,
				'retries' => 2,
				'costs' => 0,
				'unique' => false,
			],
		];

		// clear out the rate history
		$this->QueuedJobs->rateHistory = [];

		$data1 = ['key' => 1];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', $data1));
		$data2 = ['key' => 2];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', $data2));
		$data3 = ['key' => 3];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', $data3));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('dummytask'));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('dummytask'));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('dummytask'));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('dummytask'));

		//At first we get task1-1.
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('task1', $tmp['job_type']);
		$this->assertSame($data1, unserialize($tmp['data']));

		//The rate limit should now skip over task1-2 and fetch a dummytask.
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('dummytask', $tmp['job_type']);
		$this->assertFalse(unserialize($tmp['data']));

		usleep(100000);
		//and again.
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('dummytask', $tmp['job_type']);
		$this->assertFalse(unserialize($tmp['data']));

		//Then some time passes
		sleep(2);

		//Now we should get task1-2
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('task1', $tmp['job_type']);
		$this->assertSame($data2, unserialize($tmp['data']));

		//and again rate limit to dummytask.
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('dummytask', $tmp['job_type']);
		$this->assertFalse(unserialize($tmp['data']));

		//Then some more time passes
		sleep(2);

		//Now we should get task1-3
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('task1', $tmp['job_type']);
		$this->assertSame($data3, unserialize($tmp['data']));

		//and again rate limit to dummytask.
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('dummytask', $tmp['job_type']);
		$this->assertFalse(unserialize($tmp['data']));

		//and now the queue is empty
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertNull($tmp);
	}

	/**
	 * Are those tests still valid? //FIXME
	 *
	 * @return void
	 */
	public function _testRequeueAfterTimeout() {
		$capabilities = [
			'task1' => [
				'name' => 'task1',
				'timeout' => 1,
				'retries' => 2,
				'rate' => 0,
			],
		];

		$data = [
			'key' => '1',
		];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', $data));

		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('task1', $tmp['job_type']);
		$this->assertSame($data, unserialize($tmp['data']));
		$this->assertSame('0', $tmp['failed']);
		sleep(2);

		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('task1', $tmp['job_type']);
		$this->assertSame($data, unserialize($tmp['data']));
		$this->assertSame('1', $tmp['failed']);
		$this->assertSame('Restart after timeout', $tmp['failure_message']);
	}

	/**
	 * Tests whether the timeout of second tasks doesn't interfere with
	 * requeue of tasks
	 *
	 * Are those tests still valid? //FIXME
	 *
	 * @return void
	 */
	public function _testRequeueAfterTimeout2() {
		$capabilities = [
			'task1' => [
				'name' => 'task1',
				'timeout' => 1,
				'retries' => 2,
				'rate' => 0,
			],
			'task2' => [
				'name' => 'task2',
				'timeout' => 100,
				'retries' => 2,
				'rate' => 0,
			],
		];

		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', ['1']));

		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('task1', $tmp['job_type']);
		$this->assertSame(['1'], unserialize($tmp['data']));
		$this->assertSame('0', $tmp['failed']);
		sleep(2);

		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('task1', $tmp['job_type']);
		$this->assertSame(['1'], unserialize($tmp['data']));
		$this->assertSame('1', $tmp['failed']);
		$this->assertSame('Restart after timeout', $tmp['failure_message']);
	}

	/**
	 * Testing request grouping.
	 *
	 * @return void
	 */
	public function testRequestGroup() {
		$this->_needsConnection();

		$capabilities = [
			'task1' => [
				'name' => 'task1',
				'timeout' => 1,
				'retries' => 2,
				'rate' => 0,
				'costs' => 0,
				'unique' => false,
			],
		];

		// create an ungrouped task
		$data = ['key' => 1];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', $data));
		//create a Grouped Task
		$data2 = ['key' => 2];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', $data2, ['group' => 'testgroup']));

		// Fetching without group should completely ignore the Group field.
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('task1', $tmp['job_type']);
		$this->assertSame($data, unserialize($tmp['data']));

		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('task1', $tmp['job_type']);

		$this->assertSame($data2, unserialize($tmp['data']));

		// well, lets try that Again, while limiting by Group
		// create an ungrouped task
		$data3 = ['key' => 3];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', $data3));
		//create a Grouped Task
		$data4 = ['key' => 4];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', $data4, ['group' => 'testgroup', 'reference' => 'Job number 4']));
		$data5 = ['key' => 5];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', $data5, ['reference' => 'Job number 5']));
		$data6 = ['key' => 6];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', $data6, ['group' => 'testgroup', 'reference' => 'Job number 6']));

		// we should only get tasks 4 and 6, in that order, when requesting inside the group
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities, ['testgroup']);
		$this->assertSame('task1', $tmp['job_type']);
		$this->assertSame($data4, unserialize($tmp['data']));

		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities, ['testgroup', '-excluded']);
		$this->assertSame('task1', $tmp['job_type']);
		$this->assertSame($data6, unserialize($tmp['data']));

		// use FindProgress on the testgroup:
		$progress = $this->QueuedJobs->find('all', [
			'conditions' => [
				'job_group' => 'testgroup',
			],
		])->toArray();

		$this->assertSame(3, count($progress));

		$this->assertNull($progress[0]['reference']);
		$this->assertSame('Job number 4', $progress[1]['reference']);
		$this->assertSame('Job number 6', $progress[2]['reference']);
	}

	/**
	 * @return void
	 */
	public function testPriority() {
		$this->_needsConnection();

		$capabilities = [
			'task1' => [
				'name' => 'task1',
				'timeout' => 1,
				'retries' => 2,
				'rate' => 0,
				'costs' => 0,
				'unique' => false,
			],
		];

		$data = ['key' => 'k1'];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', $data));

		$data = ['key' => 'k2'];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', $data, ['priority' => 1]));

		$data = ['key' => 'k3'];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('task1', $data, ['priority' => 6]));

		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$data = unserialize($tmp['data']);
		$this->assertSame(['key' => 'k2'], $data);
	}

	/**
	 * @return void
	 */
	public function testIsQueued() {
		$result = $this->QueuedJobs->isQueued('foo-bar');
		$this->assertFalse($result);

		$queuedJob = $this->QueuedJobs->newEntity([
			'key' => 'key',
			'job_type' => 'FooBar',
			'reference' => 'foo-bar',
		]);
		$this->QueuedJobs->saveOrFail($queuedJob);

		$result = $this->QueuedJobs->isQueued('foo-bar');
		$this->assertTrue($result);

		$queuedJob->completed = new FrozenTime();
		$this->QueuedJobs->saveOrFail($queuedJob);

		$result = $this->QueuedJobs->isQueued('foo-bar');
		$this->assertFalse($result);
	}

	/**
	 * @return void
	 */
	public function testEndProcess() {
		/** @var \Queue\Model\Table\QueueProcessesTable $queuedProcessesTable */
		$queuedProcessesTable = TableRegistry::getTableLocator()->get('Queue.QueueProcesses');

		$queuedProcess = $queuedProcessesTable->newEntity([
			'pid' => 1,
			'workerkey' => '123',
		]);
		$queuedProcessesTable->saveOrFail($queuedProcess);

		$this->QueuedJobs->endProcess(1);

		$queuedProcess = $queuedProcessesTable->get($queuedProcess->id);
		$this->assertTrue($queuedProcess->terminate);
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

}
