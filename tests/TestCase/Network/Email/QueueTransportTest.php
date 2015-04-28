<?php

namespace Queue\Test\TestCase\Network\Email;

use App\Network\Email\AbstractTransport;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Queue\Network\Email\QueueTransport;
use Tools\Network\Email\Email;

/**
 * Test case
 *
 */
class QueueTransportTest extends TestCase {

	public $fixtures = [
		'plugin.Queue.QueuedTasks'
	];

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
		$Email = new Email();
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
	public function notestSendWithEmail() {
		$Email = new Email();
		$Email->from('noreply@cakephp.org', 'CakePHP Test');
		$Email->to('cake@cakephp.org', 'CakePHP');
		$Email->cc(['mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso']);
		$Email->bcc('phpnut@cakephp.org');
		$Email->subject('Testing Message');
		$Email->transport('Queue.Queue');
		$config = $Email->config();
		$this->QueueTransport->config($config);

		$result = $this->QueueTransport->send($Email);
		$this->assertEquals('Email', $result['jobtype']);
		$this->assertTrue(strlen($result['data']) < 10000);

		$output = unserialize($result['data']);
		debug($output);
	}

	public function testSendLiveEmail() {
		TableRegistry::get(['class' => 'Queue.QueuedTasks', 'table' => 'queued_tasks']);

		Configure::write('debug', 0);
		$Email = new Email();
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
