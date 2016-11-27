<?php

namespace Queue\Test\TestCase\Mailer\Transport;

use Cake\Mailer\Email;
use Cake\TestSuite\TestCase;
use Queue\Mailer\Transport\QueueTransport;

/**
 * Test case
 */
class QueueTransportTest extends TestCase {

	/**
	 * @var array
	 */
	public $fixtures = [
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * @var \Queue\Mailer\Transport\QueueTransport
	 */
	protected $QueueTransport;

	/**
	 * Setup
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->QueueTransport = new QueueTransport();
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
		$this->assertEquals('Email', $result['job_type']);
		$this->assertTrue(strlen($result['data']) < 10000);

		$output = json_decode($result['data'], true);
		$this->assertEquals('Testing Message', $output['settings']['_subject']);
	}

}
