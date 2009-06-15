<?php
/**
 * @author MGriesbach@gmail.com
 * @package QueuePlugin
 * @subpackage QueuePlugin.Tests.Models
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */

App::import('Model', 'Queue.QueuedTask');

class TestQueuedTask extends QueuedTask {
	public $name = 'TestQueuedTask';
	public $useTable = 'queued_tasks';
	public $cacheSources = false;
	public $useDbConfig = 'test_suite';
}

class QueuedTaskTestCase extends CakeTestCase {
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
	public function start() {
		parent::start();
		$this->QueuedTask = & ClassRegistry::init('TestQueuedTask');
	}

	/**
	 * Basic Instance test
	 */
	public function testCountryInstance() {
		$this->assertTrue(is_a($this->QueuedTask, 'TestQueuedTask'));
	}

	/**
	 * Test the basic create and length evaluation functions.
	 */
	public function testCreateAndCount() {
		// at first, the queue should contain 0 items.
		$this->assertEqual(0, $this->QueuedTask->getLength());

		// create a job
		$this->assertTrue($this->QueuedTask->createJob('test1', array(
			'some' => 'random',
			'test' => 'data'
		)));

		// test if queue Length is 1 now.
		$this->assertEqual(1, $this->QueuedTask->getLength());

		//create some more jobs
		$this->assertTrue($this->QueuedTask->createJob('test2', array(
			'some' => 'random',
			'test' => 'data2'
		)));
		$this->assertTrue($this->QueuedTask->createJob('test2', array(
			'some' => 'random',
			'test' => 'data3'
		)));
		$this->assertTrue($this->QueuedTask->createJob('test3', array(
			'some' => 'random',
			'test' => 'data4'
		)));

		//overall queueLength shpould now be 4
		$this->assertEqual(4, $this->QueuedTask->getLength());

		// there should be 1 task of type 'test1', one of type 'test3' and 2 of type 'test2'
		$this->assertEqual(1, $this->QueuedTask->getLength('test1'));
		$this->assertEqual(2, $this->QueuedTask->getLength('test2'));
		$this->assertEqual(1, $this->QueuedTask->getLength('test3'));
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


		$this->assertEqual(array(), $this->QueuedTask->find('all'));
		// at first, the queue should contain 0 items.
		$this->assertEqual(0, $this->QueuedTask->getLength());
		// there are no jobs, so we cant fetch any.
		$this->assertFalse($this->QueuedTask->requestJob($capabilities));
		// insert one job.
		$this->assertTrue($this->QueuedTask->createJob('task1', $testData));

		// fetch and check the first job.
		$data = $this->QueuedTask->requestJob($capabilities);
		$this->assertEqual(1, $data['id']);
		$this->assertEqual('task1', $data['jobtype']);
		$this->assertEqual(0, $data['failed']);
		$this->assertNull($data['completed']);
		$this->assertEqual($testData, unserialize($data['data']));

		// after this job has been fetched, it may not be reassigned.
		$this->assertEqual(array(), $this->QueuedTask->requestJob($capabilities));

		// queue length is still 1 since the first job did not finish.
		$this->assertEqual(1, $this->QueuedTask->getLength());

		// Now mark Task1 as done
		$this->assertTrue($this->QueuedTask->markJobDone(1));
		// Should be 0 again.
		$this->assertEqual(0, $this->QueuedTask->getLength());
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
		$this->assertEqual(0, $this->QueuedTask->getLength());
		// create some more jobs
		foreach (range(0, 9) as $num) {
			$this->assertTrue($this->QueuedTask->createJob('task1', array(
				'tasknum' => $num
			)));
		}
		// 10 jobs in the queue.
		$this->assertEqual(10, $this->QueuedTask->getLength());

		// jobs should be fetched in the original sequence.
		foreach (range(0, 4) as $num) {
			$job = $this->QueuedTask->requestJob($capabilities);
			$jobData = unserialize($job['data']);
			$this->assertEqual($num, $jobData['tasknum']);
		}
		// now mark them as done
		foreach (range(0, 4) as $num) {
			$this->assertTrue($this->QueuedTask->markJobDone($num + 1));
			$this->assertEqual(9 - $num, $this->QueuedTask->getLength());
		}

		// jobs should be fetched in the original sequence.
		foreach (range(5, 9) as $num) {
			$job = $this->QueuedTask->requestJob($capabilities);
			$jobData = unserialize($job['data']);
			$this->assertEqual($num, $jobData['tasknum']);
			$this->assertTrue($this->QueuedTask->markJobDone($job['id']));
			$this->assertEqual(9 - $num, $this->QueuedTask->getLength());
		}
	}
}
?>