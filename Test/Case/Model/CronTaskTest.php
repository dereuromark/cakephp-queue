<?php
App::uses('CronTask', 'Queue.Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class CronTaskTest extends MyCakeTestCase {

	public $fixtures = array('core.user');

	public function setUp() {
		$this->CronTask = ClassRegistry::init('Queue.CronTask');
	}

	public function tearDown() {
		unset($this->CronTask);
		ClassRegistry::flush();
	}

}