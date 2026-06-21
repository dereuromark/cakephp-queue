<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Mailer\Transport;

use Cake\Mailer\Mailer;
use Cake\TestSuite\TestCase;
use Queue\Mailer\Transport\SimpleQueueTransport;

/**
 * Test case
 */
class SimpleQueueTransportTest extends TestCase {

	/**
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * @var \Queue\Mailer\Transport\SimpleQueueTransport
	 */
	protected $QueueTransport;

	/**
	 * Setup
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->QueueTransport = new SimpleQueueTransport();
	}

	/**
	 * @return void
	 */
	public function testSendWithEmail() {
		$config = [
			'transport' => 'queue',
			'charset' => 'utf-8',
			'headerCharset' => 'utf-8',
		];

		$this->QueueTransport->setConfig($config);
		$mailer = new Mailer($config);

		$mailer->setFrom('noreply@cakephp.org', 'CakePHP Test');
		$mailer->setTo('cake@cakephp.org', 'CakePHP');
		$mailer->setCc(['mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso']);
		$mailer->setBcc('phpnut@cakephp.org');
		$mailer->setSubject('Testing Message');
		$mailer->setAttachments([
			'wow.txt' => [
				'data' => 'much wow!',
				'mimetype' => 'text/plain',
				'contentId' => 'important',
			],
		]);

		$mailer->render('Foo Bar Content');
		$mailer->setSubject("L'utilisateur n'a pas pu être enregistré");
		$mailer->setReplyTo('noreply@cakephp.org');
		$mailer->setReadReceipt('noreply2@cakephp.org');
		$mailer->setReturnPath('noreply3@cakephp.org');
		$mailer->setDomain('cakephp.org');
		$mailer->setEmailFormat('both');

		$result = $this->QueueTransport->send($mailer->getMessage());
		$this->assertSame('Queue.Email', $result['job_task']);
		$this->assertNotEmpty($result['data']);

		$output = $result['data'];

		$settings = $output['settings'];
		$this->assertSame([['noreply@cakephp.org' => 'CakePHP Test']], $settings['from']);
		$this->assertSame(['L\'utilisateur n\'a pas pu être enregistré'], $settings['subject']);
		$this->assertSame(['queue'], $settings['transport']);
		$this->assertNotEmpty($settings['attachments']);

		$this->assertNotEmpty($result['headers']);
		$this->assertTextContains('Foo Bar Content', $result['message']);
	}

}
