<?php
App::uses('CronTasksController', 'Queue.Controller');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class CronTasksControllerTest extends MyCakeTestCase {

	public function setUp() {
		$this->CronTasks = new TestCronTasksController();
		$this->CronTasks->constructClasses();
	}

	public function tearDown() {
		unset($this->CronTasks);
		ClassRegistry::flush();
	}

}

class TestCronTasksController extends CronTasksController {
	public $autoRender = false;

	public function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}
}