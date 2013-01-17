<?php
App::uses('CronShell', 'Queue.Console/Command');

class CronShellTest extends CakeTestCase {

	public $Cron;

 	public function setUp() {
 		parent::setUp();

 		$this->Cron = new CronShell();
 	}

	public function testX() {

	}
}
