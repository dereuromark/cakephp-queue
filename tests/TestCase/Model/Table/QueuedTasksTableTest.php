<?php
/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */
namespace Queue\Test\TestCase\Model\Table;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Queue\Model\Table\QueuedTasksTable Test Case
 */
class QueuedTasksTableTest extends TestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = [
		'plugin.queue.QueuedTasks',
	];

	/**
	 * Initialize Tests
	 *
	 * @return void
	 */
	public function initialize() {
		Configure::write('App.namespace', 'TestApp');
		$this->loadModel('Queue.QueuedTasks');
	}

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$config = TableRegistry::exists('QueuedTasks') ? [] : ['className' => 'Queue\Model\Table\QueuedTasksTable'];
		$this->QueuedTasks = TableRegistry::get('QueuedTasks', $config);
	}

	/**
	 * Basic Instance test
	 *
	 * @return void
	 */
	public function testQueueInstance() {
		$this->assertInstanceOf('Queue\\Model\\Table\\QueuedTasksTable', $this->QueuedTasks);
	}

	/**
	 * Test the basic create and length evaluation functions.
	 *
	 * @return void
	 */
	public function testCreateAndCount() {
		// at first, the queue should contain 0 items.
		$this->assertEquals(0, $this->QueuedTasks->getLength());

		// create a job
		$this->assertTrue((bool)$this->QueuedTasks->createJob('test1', [
			'some' => 'random',
			'test' => 'data',
		]));

		// test if queue Length is 1 now.
		$this->assertEquals(1, $this->QueuedTasks->getLength());

		//create some more jobs
		$this->assertTrue((bool)$this->QueuedTasks->createJob('test2', [
			'some' => 'random',
			'test' => 'data2',
		]));
		$this->assertTrue((bool)$this->QueuedTasks->createJob('test2', [
			'some' => 'random',
			'test' => 'data3',
		]));
		$this->assertTrue((bool)$this->QueuedTasks->createJob('test3', [
			'some' => 'random',
			'test' => 'data4',
		]));

		//overall queueLength shpould now be 4
		$this->assertEquals(4, $this->QueuedTasks->getLength());

		// there should be 1 task of type 'test1', one of type 'test3' and 2 of type 'test2'
		$this->assertEquals(1, $this->QueuedTasks->getLength('test1'));
		$this->assertEquals(2, $this->QueuedTasks->getLength('test2'));
		$this->assertEquals(1, $this->QueuedTasks->getLength('test3'));
	}

	/**
	 * Test the basic create and fetch functions.
	 *
	 * @return void
	 */
	public function testCreateAndFetch() {
		//$capabilities is a list of tasks the worker can run.
		$capabilities = [
			'task1' => [
				'name' => 'task1',
				'timeout' => 100,
				'retries' => 2,
			],
		];
		$testData = [
			'x1' => 'y1',
			'x2' => 'y2',
			'x3' => 'y3',
			'x4' => 'y4',
		];

		// start off empty.
		$this->assertEquals([], $this->QueuedTasks->find()->toArray());
		// at first, the queue should contain 0 items.
		$this->assertEquals(0, $this->QueuedTasks->getLength());
		// there are no jobs, so we cant fetch any.
		$this->assertNull($this->QueuedTasks->requestJob($capabilities));
		// insert one job.
		$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', $testData));

		// fetch and check the first job.
		$data = $this->QueuedTasks->requestJob($capabilities);
		#debug($data);
		$this->assertEquals(1, $data['id']);
		$this->assertEquals('task1', $data['jobtype']);
		$this->assertEquals(0, $data['failed']);
		$this->assertNull($data['completed']);
		$this->assertEquals($testData, unserialize($data['data']));

		// after this job has been fetched, it may not be reassigned.
		$result = $this->QueuedTasks->requestJob($capabilities);
		#debug($result);ob_flush();
		$this->assertNull($result);

		// queue length is still 1 since the first job did not finish.
		$this->assertEquals(1, $this->QueuedTasks->getLength());

		// Now mark Task1 as done
		$this->assertEquals(1, $this->QueuedTasks->markJobDone(1));
		// Should be 0 again.
		$this->assertEquals(0, $this->QueuedTasks->getLength());
	}

	/**
	 * Test the delivery of jobs in sequence, skipping fetched but not completed tasks.
	 *
	 * @return void
	 */
	public function testSequence() {
		//$capabilities is a list of tasks the worker can run.
		$capabilities = [
			'task1' => [
				'name' => 'task1',
				'timeout' => 100,
				'retries' => 2,
			],
		];
		// at first, the queue should contain 0 items.
		$this->assertEquals(0, $this->QueuedTasks->getLength());
		// create some more jobs
		foreach (range(0, 9) as $num) {
			$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', [
				'tasknum' => $num,
			]));
		}
		// 10 jobs in the queue.
		$this->assertEquals(10, $this->QueuedTasks->getLength());

		// jobs should be fetched in the original sequence.
		foreach (range(0, 4) as $num) {
			$this->QueuedTasks->clearKey();
			$job = $this->QueuedTasks->requestJob($capabilities);
			//debug($job);ob_flush();
			$jobData = unserialize($job['data']);
			//debug($jobData);ob_flush();
			$this->assertEquals($num, $jobData['tasknum']);
		}
		// now mark them as done
		foreach (range(0, 4) as $num) {
			$this->assertEquals(1, $this->QueuedTasks->markJobDone($num + 1));
			$this->assertEquals(9 - $num, $this->QueuedTasks->getLength());
		}

		// jobs should be fetched in the original sequence.
		foreach (range(5, 9) as $num) {
			$job = $this->QueuedTasks->requestJob($capabilities);
			$jobData = unserialize($job['data']);
			$this->assertEquals($num, $jobData['tasknum']);
			$this->assertEquals(1, $this->QueuedTasks->markJobDone($job['id']));
			$this->assertEquals(9 - $num, $this->QueuedTasks->getLength());
		}
	}

	/**
	 * Test creating Jobs to run close to a specified time, and strtotime parsing.
	 * Using toUnixString() function to convert Time object to timestamp, instead of strtotime 
	 *
	 * @return null
	 */
	public function testNotBefore() {
		$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', [], '+ 1 Min'));
		$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', [], '+ 1 Day'));
		$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', [], '2009-07-01 12:00:00'));
		$data = $this->QueuedTasks->find('all')->toArray();
		$this->assertWithinRange(strtotime('+ 1 Min'), $data[0]['notbefore']->toUnixString(), 60);
		$this->assertWithinRange(strtotime('+ 1 Day'), $data[1]['notbefore']->toUnixString(), 60);
		$this->assertWithinRange(strtotime('2009-07-01 12:00:00'), $data[2]['notbefore']->toUnixString(), 60);
	}

	/**
	 * Test Job reordering depending on 'notBefore' field.
	 * Jobs with an expired notbefore field should be executed before any other job without specific timing info.
	 *
	 * @return null
	 */
	public function testNotBeforeOrder() {
		$capabilities = [
			'task1' => [
				'name' => 'task1',
				'timeout' => 100,
				'retries' => 2,
			],
			'dummytask' => [
				'name' => 'dummytask',
				'timeout' => 100,
				'retries' => 2,
			],
		];
		$this->assertTrue((bool)$this->QueuedTasks->createJob('dummytask'));
		$this->assertTrue((bool)$this->QueuedTasks->createJob('dummytask'));
		// create a task with it's execution target some seconds in the past, so it should jump to the top of the list.
		$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', 'three', '- 3 Seconds'));
		$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', 'two', '- 5 Seconds'));
		$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', 'one', '- 7 Seconds'));

		// when usin requestJob, the jobs we just created should be delivered in this order, NOT the order in which they where created.
		$expected = [
			[
				'name' => 'task1',
				'data' => 'one',
			],
			[
				'name' => 'task1',
				'data' => 'two',
			],
			[
				'name' => 'task1',
				'data' => 'three',
			],
			[
				'name' => 'dummytask',
				'data' => '',
			],
			[
				'name' => 'dummytask',
				'data' => '',
			],
		];

		foreach ($expected as $item) {
			$this->QueuedTasks->clearKey();
			$tmp = $this->QueuedTasks->requestJob($capabilities);

			$this->assertEquals($item['name'], $tmp['jobtype']);
			$this->assertEquals($item['data'], unserialize($tmp['data']));
		}
	}

	/**
	 * Job Rate limiting.
	 * Do not execute jobs of a certain type more often than once every X seconds.
	 *
	 * @return void
	 */
	public function testRateLimit() {
		$capabilities = [
			'task1' => [
				'name' => 'task1',
				'timeout' => 101,
				'retries' => 2,
				'rate' => 1,
			],
			'dummytask' => [
				'name' => 'dummytask',
				'timeout' => 101,
				'retries' => 2,
			],
		];

		// clear out the rate history
		$this->QueuedTasks->rateHistory = [];

		$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', '1'));
		$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', '2'));
		$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', '3'));
		$this->assertTrue((bool)$this->QueuedTasks->createJob('dummytask', null));
		$this->assertTrue((bool)$this->QueuedTasks->createJob('dummytask', null));
		$this->assertTrue((bool)$this->QueuedTasks->createJob('dummytask', null));
		$this->assertTrue((bool)$this->QueuedTasks->createJob('dummytask', null));

		//At first we get task1-1.
		$this->QueuedTasks->clearKey();
		$tmp = $this->QueuedTasks->requestJob($capabilities);
		$this->assertEquals('task1', $tmp['jobtype']);
		$this->assertEquals('1', unserialize($tmp['data']));

		//The rate limit should now skip over task1-2 and fetch a dummytask.
		$this->QueuedTasks->clearKey();
		$tmp = $this->QueuedTasks->requestJob($capabilities);
		$this->assertEquals('dummytask', $tmp['jobtype']);
		$this->assertNull(unserialize($tmp['data']));

		usleep(500000);
		//and again.
		$this->QueuedTasks->clearKey();
		$tmp = $this->QueuedTasks->requestJob($capabilities);
		$this->assertEquals('dummytask', $tmp['jobtype']);
		$this->assertNull(unserialize($tmp['data']));

		//Then some time passes
		sleep(1);

		//Now we should get task1-2
		$this->QueuedTasks->clearKey();
		$tmp = $this->QueuedTasks->requestJob($capabilities);
		$this->assertEquals('task1', $tmp['jobtype']);
		$this->assertEquals('2', unserialize($tmp['data']));

		//and again rate limit to dummytask.
		$this->QueuedTasks->clearKey();
		$tmp = $this->QueuedTasks->requestJob($capabilities);
		$this->assertEquals('dummytask', $tmp['jobtype']);
		$this->assertNull(unserialize($tmp['data']));

		//Then some more time passes
		sleep(1);

		//Now we should get task1-3
		$this->QueuedTasks->clearKey();
		$tmp = $this->QueuedTasks->requestJob($capabilities);
		$this->assertEquals('task1', $tmp['jobtype']);
		$this->assertEquals('3', unserialize($tmp['data']));

		//and again rate limit to dummytask.
		$this->QueuedTasks->clearKey();
		$tmp = $this->QueuedTasks->requestJob($capabilities);
		$this->assertEquals('dummytask', $tmp['jobtype']);
		$this->assertNull(unserialize($tmp['data']));

		//and now the queue is empty
		$this->QueuedTasks->clearKey();
		$tmp = $this->QueuedTasks->requestJob($capabilities);
		$this->assertNull($tmp);
	}

	/**
	 * QueuedTaskTest::testRequeueAfterTimeout()
	 *
	 * @return void
	 */
	public function testRequeueAfterTimeout() {
		$capabilities = [
			'task1' => [
				'name' => 'task1',
				'timeout' => 1,
				'retries' => 2,
				'rate' => 0,
			],
		];

		$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', '1'));

		$this->QueuedTasks->clearKey();
		$tmp = $this->QueuedTasks->requestJob($capabilities);
		$this->assertEquals('task1', $tmp['jobtype']);
		$this->assertEquals('1', unserialize($tmp['data']));
		$this->assertEquals('0', $tmp['failed']);
		sleep(2);

		$this->QueuedTasks->clearKey();
		$tmp = $this->QueuedTasks->requestJob($capabilities);
		$this->assertEquals('task1', $tmp['jobtype']);
		$this->assertEquals('1', unserialize($tmp['data']));
		$this->assertEquals('1', $tmp['failed']);
		$this->assertEquals('Restart after timeout', $tmp['failure_message']);
	}

	/**
	 * Tests wheter the timeout of second tasks doesn't interfere with
	 * requeue of tasks
	 *
	 * @return void
	 */
	public function testRequeueAfterTimeout2() {
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

		$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', '1'));

		$this->QueuedTasks->clearKey();
		$tmp = $this->QueuedTasks->requestJob($capabilities);
		$this->assertEquals('task1', $tmp['jobtype']);
		$this->assertEquals('1', unserialize($tmp['data']));
		$this->assertEquals('0', $tmp['failed']);
		sleep(2);

		$this->QueuedTasks->clearKey();
		$tmp = $this->QueuedTasks->requestJob($capabilities);
		$this->assertEquals('task1', $tmp['jobtype']);
		$this->assertEquals('1', unserialize($tmp['data']));
		$this->assertEquals('1', $tmp['failed']);
		$this->assertEquals('Restart after timeout', $tmp['failure_message']);
	}

	/**
	 * Testing request grouping.
	 *
	 * @return void
	 */
	public function testRequestGroup() {
		$capabilities = [
			'task1' => [
				'name' => 'task1',
				'timeout' => 1,
				'retries' => 2,
				'rate' => 0,
			],
		];

		// create an ungrouped task
		$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', 1));
		//create a Grouped Task
		$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', 2, null, 'testgroup'));

		// Fetching without group should completely ignore the Group field.
		$this->QueuedTasks->clearKey();
		$tmp = $this->QueuedTasks->requestJob($capabilities);
		$this->assertEquals('task1', $tmp['jobtype']);
		$this->assertEquals(1, unserialize($tmp['data']));

		$this->QueuedTasks->clearKey();
		$tmp = $this->QueuedTasks->requestJob($capabilities);
		$this->assertEquals('task1', $tmp['jobtype']);

		$this->assertEquals(2, unserialize($tmp['data']));

		// well, lets tra that Again, while limiting by Group
		// create an ungrouped task
		$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', 3));
		//create a Grouped Task
		$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', 4, null, 'testgroup', 'Job number 4'));
		$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', 5, null, null, 'Job number 5'));
		$this->assertTrue((bool)$this->QueuedTasks->createJob('task1', 6, null, 'testgroup', 'Job number 6'));

		// we should only get tasks 4 and 6, in that order, when requesting inside the group
		$this->QueuedTasks->clearKey();
		$tmp = $this->QueuedTasks->requestJob($capabilities, 'testgroup');
		$this->assertEquals('task1', $tmp['jobtype']);
		$this->assertEquals(4, unserialize($tmp['data']));

		$this->QueuedTasks->clearKey();
		$tmp = $this->QueuedTasks->requestJob($capabilities, 'testgroup');
		$this->assertEquals('task1', $tmp['jobtype']);
		$this->assertEquals(6, unserialize($tmp['data']));

		// use FindProgress on the testgroup:
		$progress = $this->QueuedTasks->find('all', [
			'conditions' => [
				'task_group' => 'testgroup',
			],
		])->toArray();

		$this->assertEquals(3, count($progress));

		$this->assertNull($progress[0]['reference']);
		#$this->assertEquals($progress[0]['status'], 'IN_PROGRESS');
		$this->assertEquals('Job number 4', $progress[1]['reference']);
		#$this->assertEquals($progress[1]['status'], 'IN_PROGRESS');
		$this->assertEquals('Job number 6', $progress[2]['reference']);
		#$this->assertEquals($progress[2]['status'], 'IN_PROGRESS');
	}

}
