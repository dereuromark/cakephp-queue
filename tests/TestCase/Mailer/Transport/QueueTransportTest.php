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
		$Email->setFrom('noreply@cakephp.org', 'CakePHP Test');
		$Email->setTo('cake@cakephp.org', 'CakePHP');
		$Email->setCc(['mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso']);
		$Email->setBcc('phpnut@cakephp.org');
		$Email->setSubject('Testing Message');
		$Email->setTransport('queue');
		$config = $Email->getConfig('default');
		$this->QueueTransport->setConfig($config);

		$result = $this->QueueTransport->send($Email);
		$this->assertSame('Email', $result['job_type']);
		$this->assertTrue(strlen($result['data']) < 10000);

		//$output = unserialize($result['data']);
		//$this->assertEquals('Testing Message', $output['settings']['_subject']);
	}

}
