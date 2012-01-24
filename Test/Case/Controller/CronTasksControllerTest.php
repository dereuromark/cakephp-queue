<?php
/* CronTasks Test cases generated on: 2011-07-17 22:22:35 : 1310934155*/
App::uses('CronTasksController', 'Queue.Controller');
App::uses('MyCakeTestCase', 'Tools.Lib');

class CronTasksControllerTest extends MyCakeTestCase {
	public function startTest() {
		$this->CronTasks = new TestCronTasksController();
		$this->CronTasks->constructClasses();
	}

	public function endTest() {
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