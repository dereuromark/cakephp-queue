<?php
App::uses('CronTask', 'Queue.Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class CronTaskTest extends MyCakeTestCase {

	public $fixtures = array('core.user');

	public function setUp() {
		parent::setUp();

		$this->CronTask = ClassRegistry::init('Queue.CronTask');
	}

/**
 * QueueShellTest::testObject()
 *
 * @return void
 */
	public function testObject() {
		$this->assertTrue(is_object($this->CronTask));
		$this->assertInstanceOf('CronTask', $this->CronTask);
	}

}
