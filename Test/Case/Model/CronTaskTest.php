<?php
App::import('Model', 'Queue.CronTask');
App::uses('MyCakeTestCase', 'Tools.Lib');

class CronTaskTest extends MyCakeTestCase {
	public $fixtures = array('core.user');

	public function startTest() {
		$this->CronTask = ClassRegistry::init('Queue.CronTask');
	}

	public function endTest() {
		unset($this->CronTask);
		ClassRegistry::flush();
	}

}