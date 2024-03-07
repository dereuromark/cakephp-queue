<?php
declare(strict_types=1);

/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */

namespace Queue\Test\TestCase\Model\Table;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Queue\Model\Table\QueuedJobsTable;
use Queue\Queue\Task\ExampleTask;
use TestApp\Dto\MyTaskDto;

/**
 * Queue\Model\Table\QueuedJobsTable Test Case
 */
class QueuedJobsTableTest extends TestCase {

	/**
	 * @var \Queue\Model\Table\QueuedJobsTable
	 */
	protected $QueuedJobs;

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
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

		$config = TableRegistry::getTableLocator()->exists('QueuedJobs') ? [] : ['className' => QueuedJobsTable::class];
		$this->QueuedJobs = $this->getTableLocator()->get('QueuedJobs', $config);
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		Configure::delete('Queue.skipExistenceCheck');
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
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', [
			'some' => 'random',
			'test' => 'data',
		]));

		// test if queue Length is 1 now.
		$this->assertSame(1, $this->QueuedJobs->getLength());

		//create some more jobs
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Queue.Example', [
			'some' => 'random',
			'test' => 'data2',
		]));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Queue.Example', [
			'some' => 'random',
			'test' => 'data3',
		]));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Queue.ExceptionExample', [
			'some' => 'random',
			'test' => 'data4',
		]));

		//overall queueLength should now be 4
		$this->assertSame(4, $this->QueuedJobs->getLength());

		// there should be 1 task of type 'Foo', one of type 'Queue.ExceptionExample' and 2 of type 'Queue.Example'
		$this->assertSame(1, $this->QueuedJobs->getLength('Foo'));
		$this->assertSame(2, $this->QueuedJobs->getLength('Queue.Example'));
		$this->assertSame(1, $this->QueuedJobs->getLength('Queue.ExceptionExample'));
	}

	/**
	 * @return void
	 */
	public function testMarkJobDone() {
		$job = $this->QueuedJobs->createJob('Foo', [
			'some' => 'random',
			'test' => 'data',
		]);
		$this->assertTrue($this->QueuedJobs->markJobDone($job));
	}

	/**
	 * @return void
	 */
	public function testMarkJobFailed() {
		$job = $this->QueuedJobs->createJob('Foo', [
			'some' => 'random',
			'test' => 'data',
		]);
		$this->assertTrue($this->QueuedJobs->markJobFailed($job));
	}

	/**
	 * @return void
	 */
	public function testFlushFailedJobs() {
		$this->QueuedJobs->createJob('Foo', [
			'some' => 'random',
			'test' => 'data',
		]);
		$this->assertSame(0, $this->QueuedJobs->flushFailedJobs());
	}

	/**
	 * Test the basic create and fetch functions.
	 *
	 * @return void
	 */
	public function testCreateAndFetch() {
		//$capabilities is a list of tasks the worker can run.
		$capabilities = [
			'Foo' => [
				'name' => 'Foo',
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
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', $testData));

		// fetch and check the first job.
		$job = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame(1, $job->id);
		$this->assertSame('Foo', $job->job_task);
		$this->assertSame(1, $job->attempts);
		$this->assertNull($job->completed);
		$this->assertSame($testData, $job->data);

		// after this job has been fetched, it may not be reassigned.
		$result = $this->QueuedJobs->requestJob($capabilities);
		$this->assertNull($result);

		// queue length is still 1 since the first job did not finish.
		$this->assertSame(1, $this->QueuedJobs->getLength());

		// Now mark Foo as done
		$this->assertTrue($this->QueuedJobs->markJobDone($job));

		// Should be 0 again.
		$this->assertSame(0, $this->QueuedJobs->getLength());
	}

	/**
	 * Tests that FQCN for job task resolves to job name.
	 *
	 * @return void
	 */
	public function testCreateWithFqcn() {
		$queuedJob = $this->QueuedJobs->createJob(ExampleTask::class, [
			'some' => 'random',
			'test' => 'data',
		]);
		$this->assertTrue((bool)$queuedJob);
		$this->assertSame(ExampleTask::taskName(), $queuedJob->job_task);
	}

	/**
	 * Tests that string for job task, with "skipExistenceCheck" set to true, results in a successfully created job.
	 *
	 * @return void
	 */
	public function testCreateWithSkipExistenceCheck() {
		Configure::write('Queue.skipExistenceCheck', true);

		$queuedJob = $this->QueuedJobs->createJob('FooDoesNotExist', [
			'some' => 'random',
			'test' => 'data',
		]);
		$this->assertTrue((bool)$queuedJob);
	}

	/**
	 * @return void
	 */
	public function testCreateWithDto() {
		$array = [
			'some' => 'random',
			'test' => 'data',
		];
		$dto = new MyTaskDto($array);

		$queuedJob = $this->QueuedJobs->createJob(ExampleTask::class, $dto);
		$this->assertTrue((bool)$queuedJob);
		$this->assertSame($array, $queuedJob->data);
	}

	/**
	 * @return void
	 */
	public function testCreateWithObject() {
		$array = [
			'some' => 'random',
			'test' => 'data',
		];
		$dto = new class() {
			/**
			 * @return string[]
			 */
			public function toArray(): array {
				return [
					'some' => 'random',
					'test' => 'data',
				];
			}
		};

		$queuedJob = $this->QueuedJobs->createJob(ExampleTask::class, $dto);
		$this->assertTrue((bool)$queuedJob);
		$this->assertSame($array, $queuedJob->data);
	}

	/**
	 * Test the delivery of jobs in sequence, skipping fetched but not completed tasks.
	 *
	 * @return void
	 */
	public function testSequence() {
		//$capabilities is a list of tasks the worker can run.
		$capabilities = [
			'Foo' => [
				'name' => 'Foo',
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
			$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', [
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
			$jobData = $array[$num]['data'];
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
			$jobData = $job->data;
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
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', null, ['notBefore' => '+ 1 Min']));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', null, ['notBefore' => '+ 1 Day']));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', null, ['notBefore' => '2009-07-01 12:00:00']));
		$data = $this->QueuedJobs->find('all')->toArray();
		$this->assertWithinRange((new DateTime('+ 1 Min'))->toUnixString(), $data[0]['notbefore']->toUnixString(), 60);
		$this->assertWithinRange((new DateTime('+ 1 Day'))->toUnixString(), $data[1]['notbefore']->toUnixString(), 60);
		$this->assertWithinRange((new DateTime('2009-07-01 12:00:00'))->toUnixString(), $data[2]['notbefore']->toUnixString(), 60);
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
			'Foo' => [
				'name' => 'Foo',
				'timeout' => 100,
				'retries' => 2,
				'rate' => 0,
				'costs' => 0,
				'unique' => false,
			],
			'Queue.Example' => [
				'name' => 'Queue.Example',
				'timeout' => 100,
				'retries' => 2,
				'rate' => 0,
				'costs' => 0,
				'unique' => false,
			],
		];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Queue.Example'));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Queue.Example'));
		// create a task with it's execution target some seconds in the past, so it should jump to the top of the list.
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', ['three'], ['notBefore' => '- 3 Seconds']));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', ['two'], ['notBefore' => '- 5 Seconds']));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', ['one'], ['notBefore' => '- 7 Seconds']));

		// when using requestJob, the jobs we just created should be delivered in this order, NOT the order in which they where created.
		$expected = [
			[
				'name' => 'Foo',
				'data' => ['one'],
			],
			[
				'name' => 'Foo',
				'data' => ['two'],
			],
			[
				'name' => 'Foo',
				'data' => ['three'],
			],
			[
				'name' => 'Queue.Example',
				'data' => null,
			],
			[
				'name' => 'Queue.Example',
				'data' => null,
			],
		];

		foreach ($expected as $item) {
			$this->QueuedJobs->clearKey();
			$tmp = $this->QueuedJobs->requestJob($capabilities);

			$this->assertSame($item['name'], $tmp['job_task']);
			$dataValue = $tmp['data'];
			$this->assertEquals($item['data'], $dataValue);
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
			'Foo' => [
				'name' => 'Foo',
				'timeout' => 101,
				'retries' => 2,
				'rate' => 2,
				'costs' => 0,
				'unique' => false,
			],
			'Queue.Example' => [
				'name' => 'Queue.Example',
				'timeout' => 101,
				'retries' => 2,
				'costs' => 0,
				'unique' => false,
			],
		];

		// clear out the rate history
		$this->QueuedJobs->rateHistory = [];

		$data1 = ['key' => 1];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', $data1));
		$data2 = ['key' => 2];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', $data2));
		$data3 = ['key' => 3];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', $data3));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Queue.Example'));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Queue.Example'));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Queue.Example'));
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Queue.Example'));

		//At first we get Foo-1.
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('Foo', $tmp['job_task']);
		$this->assertSame($data1, $tmp['data']);

		//The rate limit should now skip over Foo-2 and fetch a Queue.Example.
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('Queue.Example', $tmp['job_task']);
		$this->assertNull($tmp['data']);

		usleep(100000);
		//and again.
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('Queue.Example', $tmp['job_task']);
		$this->assertNull($tmp['data']);

		//Then some time passes
		sleep(2);

		//Now we should get Foo-2
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('Foo', $tmp['job_task']);
		$this->assertSame($data2, $tmp['data']);

		//and again rate limit to Queue.Example.
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('Queue.Example', $tmp['job_task']);
		$this->assertNull($tmp['data']);

		//Then some more time passes
		sleep(2);

		//Now we should get Foo-3
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('Foo', $tmp['job_task']);
		$this->assertSame($data3, $tmp['data']);

		//and again rate limit to Queue.Example.
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('Queue.Example', $tmp['job_task']);
		$this->assertNull($tmp['data']);

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
			'Foo' => [
				'name' => 'Foo',
				'timeout' => 1,
				'retries' => 2,
				'rate' => 0,
				'costs' => 0,
				'unique' => false,
			],
		];

		$data = [
			'key' => '1',
		];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', $data));

		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('Foo', $tmp['job_task']);
		$this->assertSame($data, $tmp['data']);
		$this->assertSame(0, $tmp['attempts']);
		sleep(2);

		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('Foo', $tmp['job_task']);
		$this->assertSame($data, $tmp['data']);
		$this->assertSame(1, $tmp['attempts']);
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
			'Foo' => [
				'name' => 'Foo',
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

		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', ['1']));

		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('Foo', $tmp['job_task']);
		$this->assertSame(['1'], $tmp['data']);
		$this->assertSame('0', $tmp['attempts']);
		sleep(2);

		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('Foo', $tmp['job_task']);
		$this->assertSame(['1'], $tmp['data']);
		$this->assertSame('1', $tmp['attempts']);
		$this->assertSame('Restart after timeout', $tmp['failure_message']);
	}

	/**
	 * @return void
	 */
	public function testRequestJob() {
		$capabilities = [
			'Foo' => [
				'name' => 'Foo',
				'timeout' => 1,
				'retries' => 2,
				'rate' => 0,
				'costs' => 0,
				'unique' => false,
			],
		];

		$queuedJob = $this->QueuedJobs->requestJob($capabilities);
		$this->assertNull($queuedJob);
	}

	/**
	 * Testing request grouping.
	 *
	 * @return void
	 */
	public function testRequestGroup() {
		$capabilities = [
			'Foo' => [
				'name' => 'Foo',
				'timeout' => 1,
				'retries' => 2,
				'rate' => 0,
				'costs' => 0,
				'unique' => false,
			],
		];

		// create an ungrouped task
		$data = ['key' => 1];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', $data));
		//create a Grouped Task
		$data2 = ['key' => 2];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', $data2, ['group' => 'testgroup']));

		// Fetching without group should completely ignore the Group field.
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('Foo', $tmp['job_task']);
		$this->assertSame($data, $tmp['data']);

		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$this->assertSame('Foo', $tmp['job_task']);

		$this->assertSame($data2, $tmp['data']);

		// well, lets try that Again, while limiting by Group
		// create an ungrouped task
		$data3 = ['key' => 3];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', $data3));
		//create a Grouped Task
		$data4 = ['key' => 4];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', $data4, ['group' => 'testgroup', 'reference' => 'Job number 4']));
		$data5 = ['key' => 5];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', $data5, ['reference' => 'Job number 5']));
		$data6 = ['key' => 6];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', $data6, ['group' => 'testgroup', 'reference' => 'Job number 6']));

		// we should only get tasks 4 and 6, in that order, when requesting inside the group
		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities, ['testgroup']);
		$this->assertSame('Foo', $tmp['job_task']);
		$this->assertSame($data4, $tmp['data']);

		$this->QueuedJobs->clearKey();
		$tmp = $this->QueuedJobs->requestJob($capabilities, ['testgroup', '-excluded']);
		$this->assertSame('Foo', $tmp['job_task']);
		$this->assertSame($data6, $tmp['data']);

		// use FindProgress on the testgroup:
		$progress = $this->QueuedJobs->find(
			'all',
			conditions: [
				'job_group' => 'testgroup',
			],
		)->toArray();

		$this->assertSame(3, count($progress));

		$this->assertNull($progress[0]['reference']);
		$this->assertSame('Job number 4', $progress[1]['reference']);
		$this->assertSame('Job number 6', $progress[2]['reference']);
	}

	/**
	 * @return void
	 */
	public function testPriority() {
		$capabilities = [
			'Foo' => [
				'name' => 'Foo',
				'timeout' => 1,
				'retries' => 2,
				'rate' => 0,
				'costs' => 0,
				'unique' => false,
			],
		];

		$data = ['key' => 'k1'];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', $data));

		$data = ['key' => 'k2'];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', $data, ['priority' => 1]));

		$data = ['key' => 'k3'];
		$this->assertTrue((bool)$this->QueuedJobs->createJob('Foo', $data, ['priority' => 6]));

		$tmp = $this->QueuedJobs->requestJob($capabilities);
		$data = $tmp->data;
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
			'job_task' => 'FooBar',
			'reference' => 'foo-bar',
		]);
		$this->QueuedJobs->saveOrFail($queuedJob);

		$result = $this->QueuedJobs->isQueued('foo-bar');
		$this->assertTrue($result);

		$queuedJob->completed = new DateTime();
		$this->QueuedJobs->saveOrFail($queuedJob);

		$result = $this->QueuedJobs->isQueued('foo-bar');
		$this->assertFalse($result);
	}

	/**
	 * @return void
	 */
	public function testGetStats() {
		$queuedJob = $this->QueuedJobs->newEntity([
			'job_task' => 'Foo',
			'completed' => (new DateTime())->subHours(2),
			'fetched' => (new DateTime())->subHours(3),
			'created' => (new DateTime())->subHours(5),
		]);
		$this->QueuedJobs->saveOrFail($queuedJob);

		$stats = $this->QueuedJobs->getStats();
		$this->assertCount(1, $stats);

		$queuedJob = array_shift($stats);
		$this->assertSame(1, $queuedJob->num);
		$this->assertWithinRange(10800, (int)$queuedJob->alltime, 1);

		$this->assertWithinRange(3600, (int)$queuedJob->runtime, 1);
		$this->assertWithinRange(7200, (int)$queuedJob->fetchdelay, 1);
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
