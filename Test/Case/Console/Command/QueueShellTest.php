<?php
App::uses('QueueShell', 'Queue.Console/Command');

class QueueShellTest extends CakeTestCase {

	public $Queue;

 	public function setUp() {
 		parent::setUp();

 		$this->Queue = new QueueShell();
 	}

	public function testX() {

	}
}
