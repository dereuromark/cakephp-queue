<?php

namespace Queue\Test\TestCase\Mailer\Transport;

use Cake\Mailer\Email;
use Cake\TestSuite\TestCase;
use Queue\Mailer\Transport\SimpleQueueTransport;

/**
 * Test case
 */
class SimpleQueueTransportTest extends TestCase {

	public $fixtures = [
		'plugin.Queue.QueuedTasks',
	];

	protected $QueueTransport;

	/**
	 * Setup
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->QueueTransport = new SimpleQueueTransport();
	}

	/**
	 * Test configuration
	 *
	 * @return void
	 */
	public function testConfig() {
		$Email = new Email();
		$Email->transport('queue');
		$Email->config('default');

		$res = $Email->transport()->config();
		//debug($res);
		//$this->assertTrue(isset($res['queue']));
	}

	/**
	 * TestSend method
	 *
	 * @return void
	 */
	public function testSendWithEmail() {
		$Email = new Email();
		$Email->from('noreply@cakephp.org', 'CakePHP Test');
		$Email->to('cake@cakephp.org', 'CakePHP');
		$Email->cc(['mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso']);
		$Email->bcc('phpnut@cakephp.org');
		$Email->subject('Testing Message');
		$Email->transport('queue');
		$config = $Email->config('default');
		$this->QueueTransport->config($config);

		$result = $this->QueueTransport->send($Email);
		$this->assertEquals('Email', $result['jobtype']);
		$this->assertTrue(strlen($result['data']) < 10000);

		$output = unserialize($result['data']);
		//debug($output);
		//$this->assertEquals($Email, $output['settings']);
	}

}
