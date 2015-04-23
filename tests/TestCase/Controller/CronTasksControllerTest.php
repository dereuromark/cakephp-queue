<?php

App::uses('CronTasksController', 'Queue.Controller');
App::uses('CakeTestCase', 'TestSuite');

class CronTasksControllerTest extends CakeTestCase {

	public $CronTasks;

	public function setUp() {
		parent::setUp();

		$this->CronTasks = new TestCronTasksController(new CakeRequest(), new CakeResponse());
		$this->CronTasks->constructClasses();
	}

	public function testObject() {
		$this->assertInstanceOf('CronTasksController', $this->CronTasks);
	}

}

class TestCronTasksController extends CronTasksController {

	public $autoRender = false;

	public function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}

}
