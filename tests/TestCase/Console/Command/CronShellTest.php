<?php
App::uses('CronShell', 'Queue.Console/Command');

class CronShellTest extends CakeTestCase {

	public $Cron;

	public function setUp() {
		parent::setUp();

		$this->Cron = new CronShell();
	}

/**
 * QueueShellTest::testObject()
 *
 * @return void
 */
	public function testObject() {
		$this->assertTrue(is_object($this->Cron));
		$this->assertInstanceOf('CronShell', $this->Cron);
	}

}
