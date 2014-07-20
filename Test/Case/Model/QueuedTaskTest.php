<?php
/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */

App::uses('QueuedTask', 'Queue.Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class QueuedTaskTest extends CakeTestCase {

/**
 * @var TestQueuedTask
 */
	public $QueuedTask;

/**
 * Fixtures to load
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.queue.queued_task'
	);

/**
 * Initialize the Testcase
 *
 */
	public function setUp() {
		parent::setUp();
		$this->QueuedTask = ClassRegistry::init('TestQueuedTask');
	}

/**
 * Basic Instance test
 */
	public function testQueueInstance() {
		$this->assertTrue(is_a($this->QueuedTask, 'TestQueuedTask'));
	}

/**
 * Test the basic create and length evaluation functions.
 */
	public function testCreateAndCount() {
		// at first, the queue should contain 0 items.
		$this->assertEquals(0, $this->QueuedTask->getLength());

		// create a job
		$this->assertTrue((bool)$this->QueuedTask->createJob('test1', array(
			'some' => 'random',
			'test' => 'data'
		)));

		// test if queue Length is 1 now.
		$this->assertEquals(1, $this->QueuedTask->getLength());

		//create some more jobs
		$this->assertTrue((bool)$this->QueuedTask->createJob('test2', array(
			'some' => 'random',
			'test' => 'data2'
		)));
		$this->assertTrue((bool)$this->QueuedTask->createJob('test2', array(
			'some' => 'random',
			'test' => 'data3'
		)));
		$this->assertTrue((bool)$this->QueuedTask->createJob('test3', array(
			'some' => 'random',
			'test' => 'data4'
		)));

		//overall queueLength shpould now be 4
		$this->assertEquals(4, $this->QueuedTask->getLength());

		// there should be 1 task of type 'test1', one of type 'test3' and 2 of type 'test2'
		$this->assertEquals(1, $this->QueuedTask->getLength('test1'));
		$this->assertEquals(2, $this->QueuedTask->getLength('test2'));
		$this->assertEquals(1, $this->QueuedTask->getLength('test3'));
	}

	public function testCreateAndFetch() {
		//$capabilities is a list of tasks the worker can run.
		$capabilities = array(
			'task1' => array(
				'name' => 'task1',
				'timeout' => 100,
				'retries' => 2
			)
		);
		$testData = array(
			'x1' => 'y1',
			'x2' => 'y2',
			'x3' => 'y3',
			'x4' => 'y4'
		);

		// start off empty.
		$this->assertEquals(array(), $this->QueuedTask->find('all'));
		// at first, the queue should contain 0 items.
		$this->assertEquals(0, $this->QueuedTask->getLength());
		// there are no jobs, so we cant fetch any.
		$this->assertSame(array(), $this->QueuedTask->requestJob($capabilities));
		// insert one job.
		$this->assertTrue((bool)$this->QueuedTask->createJob('task1', $testData));

		// fetch and check the first job.
		$data = $this->QueuedTask->requestJob($capabilities);

		$this->assertEquals(1, $data['id']);
		$this->assertEquals('task1', $data['jobtype']);
		$this->assertEquals(0, $data['failed']);
		$this->assertNull($data['completed']);
		$this->assertEquals($testData, unserialize($data['data']));

		// after this job has been fetched, it may not be reassigned.
		$result = $this->QueuedTask->requestJob($capabilities);
		//debug($result);ob_flush();
		$this->assertSame(array(), $result);

		// queue length is still 1 since the first job did not finish.
		$this->assertEquals(1, $this->QueuedTask->getLength());

		// Now mark Task1 as done
		$this->assertTrue($this->QueuedTask->markJobDone(1));
		// Should be 0 again.
		$this->assertEquals(0, $this->QueuedTask->getLength());
	}

/**
 * Test the delivery of jobs in sequence, skipping fetched but not completed tasks.
 *
 * @return void
 */
	public function testSequence() {
		//$capabilities is a list of tasks the worker can run.
		$capabilities = array(
			'task1' => array(
				'name' => 'task1',
				'timeout' => 100,
				'retries' => 2
			)
		);
		// at first, the queue should contain 0 items.
		$this->assertEquals(0, $this->QueuedTask->getLength());
		// create some more jobs
		foreach (range(0, 9) as $num) {
			$this->assertTrue((bool)$this->QueuedTask->createJob('task1', array(
				'tasknum' => $num
			)));
		}
		// 10 jobs in the queue.
		$this->assertEquals(10, $this->QueuedTask->getLength());

		// jobs should be fetched in the original sequence.
		foreach (range(0, 4) as $num) {
			$this->QueuedTask->clearKey();
			$job = $this->QueuedTask->requestJob($capabilities);
			//debug($job);ob_flush();
			$jobData = unserialize($job['data']);
			//debug($jobData);ob_flush();
			$this->assertEquals($num, $jobData['tasknum']);
		}
		// now mark them as done
		foreach (range(0, 4) as $num) {
			$this->assertTrue($this->QueuedTask->markJobDone($num + 1));
			$this->assertEquals(9 - $num, $this->QueuedTask->getLength());
		}

		// jobs should be fetched in the original sequence.
		foreach (range(5, 9) as $num) {
			$job = $this->QueuedTask->requestJob($capabilities);
			$jobData = unserialize($job['data']);
			$this->assertEquals($num, $jobData['tasknum']);
			$this->assertTrue($this->QueuedTask->markJobDone($job['id']));
			$this->assertEquals(9 - $num, $this->QueuedTask->getLength());
		}
	}

/**
 * Test creating Jobs to run close to a specified time, and strtotime parsing.
 *
 * @return null
 */
	public function testNotBefore() {
		$this->assertTrue((bool)$this->QueuedTask->createJob('task1', array(), '+ 1 Min'));
		$this->assertTrue((bool)$this->QueuedTask->createJob('task1', array(), '+ 1 Day'));
		$this->assertTrue((bool)$this->QueuedTask->createJob('task1', array(), '2009-07-01 12:00:00'));
		$data = $this->QueuedTask->find('all');
		$this->assertWithinMargin(strtotime($data[0]['TestQueuedTask']['notbefore']), strtotime('+ 1 Min'), 1);
		$this->assertWithinMargin(strtotime($data[1]['TestQueuedTask']['notbefore']), strtotime('+ 1 Day'), 1);
		$this->assertWithinMargin(strtotime($data[2]['TestQueuedTask']['notbefore']), strtotime('2009-07-01 12:00:00'), 1);
	}

/**
 * Test Job reordering depending on 'notBefore' field.
 * Jobs with an expired notbefore field should be executed before any other job without specific timing info.
 *
 * @return null
 */
	public function testNotBeforeOrder() {
		$capabilities = array(
			'task1' => array(
				'name' => 'task1',
				'timeout' => 100,
				'retries' => 2
			),
			'dummytask' => array(
				'name' => 'dummytask',
				'timeout' => 100,
				'retries' => 2
			)
		);
		$this->assertTrue((bool)$this->QueuedTask->createJob('dummytask'));
		$this->assertTrue((bool)$this->QueuedTask->createJob('dummytask'));
		// create a task with it's execution target some seconds in the past, so it should jump to the top of the list.
		$this->assertTrue((bool)$this->QueuedTask->createJob('task1', 'three', '- 3 Seconds'));
		$this->assertTrue((bool)$this->QueuedTask->createJob('task1', 'two', '- 5 Seconds'));
		$this->assertTrue((bool)$this->QueuedTask->createJob('task1', 'one', '- 7 Seconds'));

		// when usin requestJob, the jobs we just created should be delivered in this order, NOT the order in which they where created.
		$expected = array(
			array(
				'name' => 'task1',
				'data' => 'one'
			),
			array(
				'name' => 'task1',
				'data' => 'two'
			),
			array(
				'name' => 'task1',
				'data' => 'three'
			),
			array(
				'name' => 'dummytask',
				'data' => ''
			),
			array(
				'name' => 'dummytask',
				'data' => ''
			)
		);

		foreach ($expected as $item) {
			$this->QueuedTask->clearKey();
			$tmp = $this->QueuedTask->requestJob($capabilities);

			$this->assertEquals($item['name'], $tmp['jobtype']);
			$this->assertEquals($item['data'], unserialize($tmp['data']));
		}
	}

/**
 * Job Rate limiting.
 * Do not execute jobs of a certain type more often than once every X seconds.
 */
	public function testRateLimit() {
		$capabilities = array(
			'task1' => array(
				'name' => 'task1',
				'timeout' => 101,
				'retries' => 2,
				'rate' => 1
			),
			'dummytask' => array(
				'name' => 'dummytask',
				'timeout' => 101,
				'retries' => 2
			)
		);

		// clear out the rate history
		$this->QueuedTask->rateHistory = array();

		$this->assertTrue((bool)$this->QueuedTask->createJob('task1', '1'));
		$this->assertTrue((bool)$this->QueuedTask->createJob('task1', '2'));
		$this->assertTrue((bool)$this->QueuedTask->createJob('task1', '3'));
		$this->assertTrue((bool)$this->QueuedTask->createJob('dummytask', null));
		$this->assertTrue((bool)$this->QueuedTask->createJob('dummytask', null));
		$this->assertTrue((bool)$this->QueuedTask->createJob('dummytask', null));
		$this->assertTrue((bool)$this->QueuedTask->createJob('dummytask', null));

		//At first we get task1-1.
		$this->QueuedTask->clearKey();
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals('task1', $tmp['jobtype']);
		$this->assertEquals('1', unserialize($tmp['data']));

		//The rate limit should now skip over task1-2 and fetch a dummytask.
		$this->QueuedTask->clearKey();
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals('dummytask', $tmp['jobtype']);
		$this->assertNull(unserialize($tmp['data']));

		//and again.
		$this->QueuedTask->clearKey();
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals('dummytask', $tmp['jobtype']);
		$this->assertNull(unserialize($tmp['data']));

		//Then some time passes
		sleep(1);

		//Now we should get task1-2
		$this->QueuedTask->clearKey();
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'task1');
		$this->assertEquals('2', unserialize($tmp['data']));

		//and again rate limit to dummytask.
		$this->QueuedTask->clearKey();
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'dummytask');
		$this->assertNull(unserialize($tmp['data']));

		//Then some more time passes
		sleep(1);

		//Now we should get task1-3
		$this->QueuedTask->clearKey();
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'task1');
		$this->assertEquals('3', unserialize($tmp['data']));

		//and again rate limit to dummytask.
		$this->QueuedTask->clearKey();
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'dummytask');
		$this->assertNull(unserialize($tmp['data']));

		//and now the queue is empty
		$this->QueuedTask->clearKey();
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertSame(array(), $tmp);
	}

/**
 * QueuedTaskTest::testRequeueAfterTimeout()
 *
 * @return void
 */
	public function testRequeueAfterTimeout() {
		$capabilities = array(
			'task1' => array(
				'name' => 'task1',
				'timeout' => 1,
				'retries' => 2,
				'rate' => 0
			)
		);

		$this->assertTrue((bool)$this->QueuedTask->createJob('task1', '1'));

		$this->QueuedTask->clearKey();
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'task1');
		$this->assertEquals('1', unserialize($tmp['data']));
		$this->assertEquals($tmp['failed'], '0');
		sleep(2);

		$this->QueuedTask->clearKey();
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'task1');
		$this->assertEquals('1', unserialize($tmp['data']));
		$this->assertEquals($tmp['failed'], '1');
		$this->assertEquals($tmp['failure_message'], 'Restart after timeout');
	}

/**
 * Tests wheter the timeout of second tasks doesn't interfere with
 * requeue of tasks
 *
 * @return void
 */
	public function testRequeueAfterTimeout2() {
		$capabilities = array(
			'task1' => array(
				'name' => 'task1',
				'timeout' => 1,
				'retries' => 2,
				'rate' => 0
			),
			'task2' => array(
				'name' => 'task2',
				'timeout' => 100,
				'retries' => 2,
				'rate' => 0
			)
		);

		$this->assertTrue((bool)$this->QueuedTask->createJob('task1', '1'));

		$this->QueuedTask->clearKey();
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'task1');
		$this->assertEquals('1', unserialize($tmp['data']));
		$this->assertEquals($tmp['failed'], '0');
		sleep(2);

		$this->QueuedTask->clearKey();
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'task1');
		$this->assertEquals('1', unserialize($tmp['data']));
		$this->assertEquals($tmp['failed'], '1');
		$this->assertEquals($tmp['failure_message'], 'Restart after timeout');
	}

	public function testRequestGroup() {
		$capabilities = array(
			'task1' => array(
				'name' => 'task1',
				'timeout' => 1,
				'retries' => 2,
				'rate' => 0
			)
		);

		// create an ungrouped task
		$this->assertTrue((bool)$this->QueuedTask->createJob('task1', 1));
		//create a Grouped Task
		$this->assertTrue((bool)$this->QueuedTask->createJob('task1', 2, null, 'testgroup'));

		// Fetching without group should completely ignore the Group field.
		$this->QueuedTask->clearKey();
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'task1');
		$this->assertEquals(1, unserialize($tmp['data']));

		$this->QueuedTask->clearKey();
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'task1');

		$this->assertEquals(2, unserialize($tmp['data']));

		// well, lets tra that Again, while limiting by Group
		// create an ungrouped task
		$this->assertTrue((bool)$this->QueuedTask->createJob('task1', 3));
		//create a Grouped Task
		$this->assertTrue((bool)$this->QueuedTask->createJob('task1', 4, null, 'testgroup', 'Job number 4'));
		$this->assertTrue((bool)$this->QueuedTask->createJob('task1', 5, null, null, 'Job number 5'));
		$this->assertTrue((bool)$this->QueuedTask->createJob('task1', 6, null, 'testgroup', 'Job number 6'));

		// we should only get tasks 4 and 6, in that order, when requesting inside the group
		$this->QueuedTask->clearKey();
		$tmp = $this->QueuedTask->requestJob($capabilities, 'testgroup');
		$this->assertEquals($tmp['jobtype'], 'task1');
		$this->assertEquals(unserialize($tmp['data']), 4);

		$this->QueuedTask->clearKey();
		$tmp = $this->QueuedTask->requestJob($capabilities, 'testgroup');
		$this->assertEquals($tmp['jobtype'], 'task1');
		$this->assertEquals(6, unserialize($tmp['data']));

		// use FindProgress on the testgroup:
		$progress = $this->QueuedTask->find('progress', array(
			'conditions' => array(
				'group' => 'testgroup'
			)
		));

		$this->assertEquals(3, count($progress));

		$this->assertNull($progress[0]['reference']);
		$this->assertEquals($progress[0]['status'], 'IN_PROGRESS');
		$this->assertEquals($progress[1]['reference'], 'Job number 4');
		$this->assertEquals($progress[1]['status'], 'IN_PROGRESS');
		$this->assertEquals($progress[2]['reference'], 'Job number 6');
		$this->assertEquals($progress[2]['status'], 'IN_PROGRESS');
	}

}

/*** other classes **/

class TestQueuedTask extends QueuedTask {

	public $useTable = 'queued_tasks';

	public $cacheSources = false;

	public function clearKey() {
		$this->_key = null;
	}

}
