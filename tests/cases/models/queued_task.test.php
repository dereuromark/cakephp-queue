<?php
/**
 * @author MGriesbach@gmail.com
 * @package QueuePlugin
 * @subpackage QueuePlugin.Tests.Models
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
	public function xtestCreateAndCount() {
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
			'task1', 
			'task2'
		);
		// at first, the queue should contain 0 items.
		$this->assertEqual(0, $this->QueuedTask->getLength());
		// there are no jobs, so we cant fetch any.
		$this->assertFalse($this->QueuedTask->requestJob($capabilities));
		// now add one job of a type we can run
		$this->assertTrue($this->QueuedTask->createJob('task1', array(
			'some' => 'random', 
			'test' => 'data'
		)));
		
		$expected = array(
			'id' => '1', 
			'jobtype' => 'task1', 
			'data' => 'a:2:{s:4:"some";s:6:"random";s:4:"test";s:4:"data";}', 
			'completed' => NULL, 
			'failed' => '0'
		);
		// check if we can get it back...
		$data = $this->QueuedTask->requestJob($capabilities);
		// remove created key since it messes up the compare.
		unset($data['created'], $data['fetched']);
		$this->assertEqual($data, $expected);
		// the job should NOT be reassigned until it times out.
		$this->assertFalse($this->QueuedTask->requestJob($capabilities));
		
		// well, let's mark this job as failed and see if it gets requeued.
		$this->assertTrue($this->QueuedTask->markJobFailed(1));
		
		// lets update the expectations
		$expected['failed'] = 1;
		$data = $this->QueuedTask->requestJob($capabilities);
		// remove created key since it messes up the compare.
		unset($data['created'], $data['fetched']);
		$this->assertEqual($data, $expected);
	
	}
}
?>