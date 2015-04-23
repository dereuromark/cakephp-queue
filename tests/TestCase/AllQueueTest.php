<?php
/**
 * Group test - Queue
 */
class AllQueueTest extends PHPUnit_Framework_TestSuite {

/**
 * Suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$Suite = new CakeTestSuite('All Queue tests');
		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Controller');
		$Suite->addTestDirectory($path . DS . 'Model');
		$Suite->addTestDirectory($path . DS . 'Console' . DS . 'Command');
		return $Suite;
	}

}
