<?php

App::uses('EmailLib', 'Tools.Lib');
App::uses('AbstractTransport', 'Network/Email');
App::uses('QueueTransport', 'Queue.Network/Email');

/**
 * Test case
 *
 */
class QueueTransportTest extends CakeTestCase {

	public $fixtures = array('plugin.queue.queued_task');

/**
 * Setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->QueueTransport = new QueueTransport();
	}

	public function testConfig() {
		$Email = new EmailLib();
		$Email->transport('Queue.Queue');
		$Email->config('default');

		$res = $Email->transportClass()->config();
		$this->assertTrue(isset($res['queue']));
	}

/**
 * TestSend method
 *
 * @return void
 */
	public function notestSendWithEmailLib() {
		$Email = new EmailLib();
		$Email->from('noreply@cakephp.org', 'CakePHP Test');
		$Email->to('cake@cakephp.org', 'CakePHP');
		$Email->cc(array('mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso'));
		$Email->bcc('phpnut@cakephp.org');
		$Email->subject('Testing Message');
		$Email->transport('Queue.Queue');
		$config = $Email->config();
		$this->QueueTransport->config($config);

		$result = $this->QueueTransport->send($Email);
		$this->assertEquals('Email', $result['QueuedTask']['jobtype']);
		$this->assertTrue(strlen($result['QueuedTask']['data']) < 10000);

		$output = unserialize($result['QueuedTask']['data']);
		debug($output);
	}

	public function testSendLiveEmail() {
		ClassRegistry::init(array('class' => 'Queue.QueuedTask', 'table' => 'queued_tasks', 'prefix' => 'site_'));

		Configure::write('debug', 0);
		$Email = new EmailLib();
		$Email->to('markscherer@gmx.de', 'Mark Test');
		$Email->subject('Testing Message');

		$config = $Email->config();
		Configure::write('debug', 2);
		debug($config);
		$this->skipIf(!isset($config['queue']), 'queue key in config missing');
		Configure::write('debug', 0);

		$res = $Email->send('Foo');
		Configure::write('debug', 2);
		if (!$res) {
			debug($Email->getError());
		}

		debug($res);
	}

}
