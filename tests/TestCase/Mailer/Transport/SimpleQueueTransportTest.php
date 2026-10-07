<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Mailer\Transport;

use Cake\Console\ConsoleIo;
use Cake\Mailer\Mailer;
use Cake\TestSuite\TestCase;
use Queue\Console\Io;
use Queue\Mailer\Transport\SimpleQueueTransport;
use Queue\Queue\Task\EmailTask;
use Shim\TestSuite\ConsoleOutput;

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
		$this->assertSame('queue', $output['transport']);
		$this->assertArrayHasKey('wow.txt', $settings['attachments']);
		$this->assertStringContainsString('Foo Bar Content', $settings['textMessage']);

		$this->assertNotEmpty($result['headers']);
		$this->assertTextContains('Foo Bar Content', $result['message']);
	}

	/**
	 * The queued payload must produce the same mail when the EmailTask runs it.
	 *
	 * @return void
	 */
	public function testSendRoundTripThroughEmailTask() {
		$this->QueueTransport->setConfig(['transport' => 'default']);
		$mailer = new Mailer();
		$mailer->setFrom('noreply@cakephp.org')
			->setTo('cake@cakephp.org')
			->setSubject('Round trip')
			->setEmailFormat('both')
			->setAttachments(['wow.txt' => ['data' => 'much wow!', 'mimetype' => 'text/plain']]);
		$mailer->getMessage()->setHeaders(['X-Custom' => 'yes']);
		$mailer->render('Foo Bar Content');

		$result = $this->QueueTransport->send($mailer->getMessage());

		$task = new EmailTask(new Io(new ConsoleIo(new ConsoleOutput(), new ConsoleOutput())));
		$task->run(json_decode(json_encode($result['data']), true), 0);

		$message = $task->mailer->getMessage();
		$this->assertStringContainsString('Foo Bar Content', $message->getBodyText());
		$this->assertStringContainsString('Foo Bar Content', $message->getBodyHtml());
		$this->assertArrayHasKey('wow.txt', $message->getAttachments());
		$this->assertSame('yes', $message->getHeaders()['X-Custom']);
		$this->assertArrayNotHasKey(0, $message->getHeaders());
	}

}
