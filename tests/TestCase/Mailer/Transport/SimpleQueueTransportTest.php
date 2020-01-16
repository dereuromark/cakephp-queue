<?php

namespace Queue\Test\TestCase\Mailer\Transport;

use Cake\Mailer\Email;
use Cake\TestSuite\TestCase;
use Queue\Mailer\Transport\SimpleQueueTransport;

/**
 * Test case
 */
class SimpleQueueTransportTest extends TestCase {

	/**
	 * @var array
	 */
	public $fixtures = [
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
	public function setUp() {
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
		$Email = new Email($config);

		$Email->setFrom('noreply@cakephp.org', 'CakePHP Test');
		$Email->setTo('cake@cakephp.org', 'CakePHP');
		$Email->setCc(['mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso']);
		$Email->setBcc('phpnut@cakephp.org');
		$Email->setSubject('Testing Message');
		$Email->setAttachments(['wow.txt' => [
			'data' => 'much wow!',
			'mimetype' => 'text/plain',
			'contentId' => 'important',
		]]);

		$Email->viewBuilder()->setLayout('test_layout');
		$Email->viewBuilder()->setTemplate('test_template');
		$Email->viewBuilder()->setTheme('EuroTheme');
		$Email->setSubject("L'utilisateur n'a pas pu être enregistré");
		$Email->setReplyTo('noreply@cakephp.org');
		$Email->setReadReceipt('noreply2@cakephp.org');
		$Email->setReturnPath('noreply3@cakephp.org');
		$Email->setDomain('cakephp.org');
		$Email->setEmailFormat('both');
		$Email->set('var1', 1);
		$Email->set('var2', 2);

		$result = $this->QueueTransport->send($Email);
		$this->assertSame('Email', $result['job_type']);
		$this->assertTrue(strlen($result['data']) < 10000);

		$output = unserialize($result['data']);
		$emailReconstructed = new Email($config);

		foreach ($output['settings'] as $method => $setting) {
			$setter = 'set' . ucfirst($method);
			if (in_array($method, ['theme', 'template', 'layout'], true)) {
				call_user_func_array([$emailReconstructed->viewBuilder(), $setter], (array)$setting);

				continue;
			}

			call_user_func_array([$emailReconstructed, $setter], (array)$setting);
		}

		$this->assertEquals($emailReconstructed->getFrom(), $Email->getFrom());
		$this->assertEquals($emailReconstructed->getTo(), $Email->getTo());
		$this->assertEquals($emailReconstructed->getCc(), $Email->getCc());
		$this->assertEquals($emailReconstructed->getBcc(), $Email->getBcc());
		$this->assertEquals($emailReconstructed->getSubject(), $Email->getSubject());
		$this->assertEquals($emailReconstructed->getCharset(), $Email->getCharset());
		$this->assertEquals($emailReconstructed->getHeaderCharset(), $Email->getHeaderCharset());
		$this->assertEquals($emailReconstructed->getEmailFormat(), $Email->getEmailFormat());
		$this->assertEquals($emailReconstructed->getReplyTo(), $Email->getReplyTo());
		$this->assertEquals($emailReconstructed->getReadReceipt(), $Email->getReadReceipt());
		$this->assertEquals($emailReconstructed->getReturnPath(), $Email->getReturnPath());
		$this->assertEquals($emailReconstructed->getDomain(), $Email->getDomain());
		$this->assertEquals($emailReconstructed->viewBuilder()->getTheme(), $Email->viewBuilder()->getTheme());
		$this->assertEquals($emailReconstructed->getProfile(), $Email->getProfile());
		$this->assertEquals($emailReconstructed->getViewVars(), $Email->getViewVars());
		$this->assertEquals($emailReconstructed->viewBuilder()->getTemplate(), $Email->viewBuilder()->getTemplate());
		$this->assertEquals($emailReconstructed->viewBuilder()->getLayout(), $Email->viewBuilder()->getLayout());
	}

}
