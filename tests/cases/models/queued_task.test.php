<?php
/**
 * @author MGriesbach@gmail.com
 * @package QueuePlugin
 * @subpackage QueuePlugin.Tests.Models
 */

App::import('Model', 'Queue.QueuedTask');

class TestQueuedTask extends QueuedTask {
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
			'test' => 'data2'
		)));
		$this->assertTrue($this->QueuedTask->createJob('test3', array(
			'some' => 'random',
			'test' => 'data2'
		)));

		//overall queueLength shpould now be 4
		$this->assertEqual(4, $this->QueuedTask->getLength());

		// there should be 1 task of type 'test1', one of type 'test3' and 2 of type 'test2'
		$this->assertEqual(1, $this->QueuedTask->getLength('test1'));
		$this->assertEqual(2, $this->QueuedTask->getLength('test2'));
		$this->assertEqual(1, $this->QueuedTask->getLength('test3'));
	}
}
?>