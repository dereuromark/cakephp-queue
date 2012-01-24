<?php
App::import('Model', 'Queue.QueuedTask');
App::uses('MyCakeTestCase', 'Tools.Lib');

/**
 * @author MGriesbach@gmail.com
 * @package QueuePlugin
 * @subpackage QueuePlugin.Tests.Models
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */
class QueuedTaskTest extends MyCakeTestCase {
	/**
	 * ZendStudio Codehint
	 *
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
		$this->assertTrue($this->QueuedTask->createJob('test1', array(
			'some' => 'random',
			'test' => 'data'
		)) !== false);
		
		// test if queue Length is 1 now.
		$this->assertEquals(1, $this->QueuedTask->getLength());
		
		//create some more jobs
		$this->assertTrue($this->QueuedTask->createJob('test2', array(
			'some' => 'random',
			'test' => 'data2'
		)) !== false);
		$this->assertTrue($this->QueuedTask->createJob('test2', array(
			'some' => 'random',
			'test' => 'data3'
		)) !== false);
		$this->assertTrue($this->QueuedTask->createJob('test3', array(
			'some' => 'random',
			'test' => 'data4'
		))!== false);
		
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
		$this->assertFalse($this->QueuedTask->requestJob($capabilities));
		// insert one job.
		$this->assertTrue($this->QueuedTask->createJob('task1', $testData) !== false);
		
		// fetch and check the first job.
		$data = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals(1, $data['id']);
		$this->assertEquals('task1', $data['jobtype']);
		$this->assertEquals(0, $data['failed']);
		$this->assertNull($data['completed']);
		$this->assertEquals($testData, unserialize($data['data']));
		
		// after this job has been fetched, it may not be reassigned.
		$this->assertFalse($this->QueuedTask->requestJob($capabilities));
		
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
			$this->assertTrue($this->QueuedTask->createJob('task1', array(
				'tasknum' => $num
			)) !== false);
		}
		// 10 jobs in the queue.
		$this->assertEquals(10, $this->QueuedTask->getLength());
		
		// jobs should be fetched in the original sequence.
		foreach (range(0, 4) as $num) {
			$job = $this->QueuedTask->requestJob($capabilities);
			$jobData = unserialize($job['data']);
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
	 * @return null
	 */
	public function testNotBefore() {
		$this->assertTrue($this->QueuedTask->createJob('task1', null, '+ 1 Min') !== false);
		$this->assertTrue($this->QueuedTask->createJob('task1', null, '+ 1 Day') !== false);
		$this->assertTrue($this->QueuedTask->createJob('task1', null, '2009-07-01 12:00:00') !== false);
		$data = $this->QueuedTask->find('all');
		$this->assertEquals($data[0]['QueuedTask']['notbefore'], date('Y-m-d H:i:s', strtotime('+ 1 Min')));
		$this->assertEquals($data[1]['QueuedTask']['notbefore'], date('Y-m-d H:i:s', strtotime('+ 1 Day')));
		$this->assertEquals($data[2]['QueuedTask']['notbefore'], '2009-07-01 12:00:00');
	}

	/**
	 * Test Job reordering depending on 'notBefore' field.
	 * Jobs with an expired notbefore field should be executed before any other job without specific timing info.
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
		$this->assertTrue($this->QueuedTask->createJob('dummytask', null) !== false);
		$this->assertTrue($this->QueuedTask->createJob('dummytask', null) !== false);
		// create a task with it's execution target some seconds in the past, so it should jump to the top of the list.
		$this->assertTrue($this->QueuedTask->createJob('task1', 'three', '- 3 Seconds') !== false);
		$this->assertTrue($this->QueuedTask->createJob('task1', 'two', '- 4 Seconds') !== false);
		$this->assertTrue($this->QueuedTask->createJob('task1', 'one', '- 5 Seconds') !== false);
		
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
				'timeout' => 100,
				'retries' => 2,
				'rate' => 1
			),
			'dummytask' => array(
				'name' => 'dummytask',
				'timeout' => 100,
				'retries' => 2
			)
		);
		
		// clear out the rate history
		$this->QueuedTask->rateHistory = array();
		
		$this->assertTrue($this->QueuedTask->createJob('task1', '1') !== false);
		$this->assertTrue($this->QueuedTask->createJob('task1', '2') !== false);
		$this->assertTrue($this->QueuedTask->createJob('task1', '3') !== false);
		$this->assertTrue($this->QueuedTask->createJob('dummytask', null) !== false);
		$this->assertTrue($this->QueuedTask->createJob('dummytask', null) !== false);
		$this->assertTrue($this->QueuedTask->createJob('dummytask', null) !== false);
		$this->assertTrue($this->QueuedTask->createJob('dummytask', null) !== false);
		
		//At first we get task1-1.
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'task1');
		$this->assertEquals(unserialize($tmp['data']), '1');
		
		//The rate limit should now skip over task1-2 and fetch a dummytask.
		$tmp = $this->QueuedTask->requestJob($capabilities);
		//pr($tmp); ob_flush();
		$this->assertEquals($tmp['jobtype'], 'dummytask');
		$this->assertEquals(unserialize($tmp['data']), null);
		
		//and again.
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'dummytask');
		$this->assertEquals(unserialize($tmp['data']), null);
		
		//Then some time passes
		sleep(1);
		
		//Now we should get task1-2
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'task1');
		$this->assertEquals(unserialize($tmp['data']), '2');
		
		//and again rate limit to dummytask.
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'dummytask');
		$this->assertEquals(unserialize($tmp['data']), null);
		
		//Then some more time passes
		sleep(1);
		
		//Now we should get task1-3
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'task1');
		$this->assertEquals(unserialize($tmp['data']), '3');
		
		//and again rate limit to dummytask.
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'dummytask');
		$this->assertEquals(unserialize($tmp['data']), null);
		
		//and now the queue is empty
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp, null);
	}

	public function testRequeueAfterTimeout() {
		$capabilities = array(
			'task1' => array(
				'name' => 'task1',
				'timeout' => 1,
				'retries' => 2,
				'rate' => 0
			)
		);
		
		$this->assertTrue($this->QueuedTask->createJob('task1', '1') !== false);
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'task1');
		$this->assertEquals(unserialize($tmp['data']), '1');
		$this->assertEquals($tmp['failed'], '0');
		sleep(2);
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'task1');
		$this->assertEquals(unserialize($tmp['data']), '1');
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
		$this->assertTrue($this->QueuedTask->createJob('task1', 1) !== false);
		//create a Grouped Task
		$this->assertTrue($this->QueuedTask->createJob('task1', 2, null, 'testgroup') !== false);
		
		// Fetching without group should completely ignore the Group field.
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'task1');
		$this->assertEquals(unserialize($tmp['data']), 1);
		$tmp = $this->QueuedTask->requestJob($capabilities);
		$this->assertEquals($tmp['jobtype'], 'task1');
		$this->assertEquals(unserialize($tmp['data']), 2);
		
		// well, lets tra that Again, while limiting by Group
		// create an ungrouped task
		$this->assertTrue($this->QueuedTask->createJob('task1', 3) !== false);
		//create a Grouped Task
		$this->assertTrue($this->QueuedTask->createJob('task1', 4, null, 'testgroup', 'Job number 4') !== false);
		$this->assertTrue($this->QueuedTask->createJob('task1', 5, null, null, 'Job number 5') !== false);
		$this->assertTrue($this->QueuedTask->createJob('task1', 6, null, 'testgroup', 'Job number 6') !== false);
		
		// we should only get tasks 4 and 6, in that order, when requesting inside the group
		$tmp = $this->QueuedTask->requestJob($capabilities, 'testgroup');
		$this->assertEquals($tmp['jobtype'], 'task1');
		$this->assertEquals(unserialize($tmp['data']), 4);
		$tmp = $this->QueuedTask->requestJob($capabilities, 'testgroup');
		$this->assertEquals($tmp['jobtype'], 'task1');
		$this->assertEquals(unserialize($tmp['data']), 6);
		
		// use FindProgress on the testgroup:
		$progress = $this->QueuedTask->find('progress', array(
			'conditions' => array(
				'group' => 'testgroup'
			)
		));
		
		$this->assertEquals(count($progress), 3);
		
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
	
	public $name = 'QueuedTask';
	public $alias = 'QueuedTask';
	
	public $cacheSources = false;
	
}