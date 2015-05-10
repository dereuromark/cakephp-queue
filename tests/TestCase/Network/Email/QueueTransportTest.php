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
