<?php
declare(strict_types=1);

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
	protected array $fixtures = [
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
		$this->assertSame('Queue.Email', $result['job_task']);
		$this->assertNotEmpty($result['data']);

		$output = $result['data'];
		$this->assertInstanceOf(Message::class, $output['settings']);

		/** @var \Cake\Mailer\Message $message */
		$message = $output['settings'];
		$this->assertSame('Testing Message', $message->getSubject());
	}

}
