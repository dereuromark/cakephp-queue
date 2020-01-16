<?php

namespace Queue\Test\TestCase\Mailer\Transport;

use Cake\Mailer\Message;
use Cake\TestSuite\TestCase;
use Queue\Mailer\Transport\QueueTransport;

/**
 * Test case
 */
class QueueTransportTest extends TestCase {

	/**
	 * @var array
	 */
	protected $fixtures = [
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
	public function setUp(): void {
		parent::setUp();
		$this->QueueTransport = new QueueTransport();
	}

	/**
	 * TestSend method
	 *
	 * @return void
	 */
	public function testSendWithEmail() {
		$message = new Message();
		$message->setFrom('noreply@cakephp.org', 'CakePHP Test');
		$message->setTo('cake@cakephp.org', 'CakePHP');
		$message->setCc(['mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso']);
		$message->setBcc('phpnut@cakephp.org');
		$message->setSubject('Testing Message');

		$result = $this->QueueTransport->send($message);
		$this->assertSame('Email', $result['job_type']);
		$this->assertTrue(strlen($result['data']) < 10000);

		$output = unserialize($result['data']);
		$this->assertInstanceOf(Message::class, $output['settings']);

		/** @var \Cake\Mailer\Message $message */
		$message = $output['settings'];
		$this->assertSame('Testing Message', $message->getSubject());
	}

}
