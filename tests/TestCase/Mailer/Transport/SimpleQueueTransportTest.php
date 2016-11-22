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

		$this->QueueTransport->config($config);
		$Email = new Email($config);

		$Email->from('noreply@cakephp.org', 'CakePHP Test');
		$Email->to('cake@cakephp.org', 'CakePHP');
		$Email->cc(['mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso']);
		$Email->bcc('phpnut@cakephp.org');
		$Email->subject('Testing Message');
		$Email->attachments(['wow.txt' => [
			'data' => 'much wow!',
			'mimetype' => 'text/plain',
			'contentId' => 'important'
		]]);

		$Email->template('test_template', 'test_layout');
		$Email->subject("L'utilisateur n'a pas pu être enregistré");
		$Email->replyTo('noreply@cakephp.org');
		$Email->readReceipt('noreply2@cakephp.org');
		$Email->returnPath('noreply3@cakephp.org');
		$Email->domain('cakephp.org');
		$Email->theme('EuroTheme');
		$Email->emailFormat('both');
		$Email->set('var1', 1);
		$Email->set('var2', 2);

		$result = $this->QueueTransport->send($Email);
		$this->assertEquals('Email', $result['job_type']);
		$this->assertTrue(strlen($result['data']) < 10000);

		$output = json_decode($result['data'], true);
		$emailReconstructed = new Email($config);

		foreach ($output['settings'] as $method => $setting) {
			call_user_func_array([$emailReconstructed, $method], (array)$setting);
		}

		$this->assertEquals($emailReconstructed->from(), $Email->from());
		$this->assertEquals($emailReconstructed->to(), $Email->to());
		$this->assertEquals($emailReconstructed->cc(), $Email->cc());
		$this->assertEquals($emailReconstructed->bcc(), $Email->bcc());
		$this->assertEquals($emailReconstructed->subject(), $Email->subject());
		$this->assertEquals($emailReconstructed->charset(), $Email->charset());
		$this->assertEquals($emailReconstructed->headerCharset(), $Email->headerCharset());
		$this->assertEquals($emailReconstructed->emailFormat(), $Email->emailFormat());
		$this->assertEquals($emailReconstructed->replyTo(), $Email->replyTo());
		$this->assertEquals($emailReconstructed->readReceipt(), $Email->readReceipt());
		$this->assertEquals($emailReconstructed->returnPath(), $Email->returnPath());
		$this->assertEquals($emailReconstructed->messageId(), $Email->messageId());
		$this->assertEquals($emailReconstructed->domain(), $Email->domain());
		$this->assertEquals($emailReconstructed->theme(), $Email->theme());
		$this->assertEquals($emailReconstructed->profile(), $Email->profile());
		$this->assertEquals($emailReconstructed->viewVars(), $Email->viewVars());
		$this->assertEquals($emailReconstructed->template(), $Email->template());

		//for now cannot be done 'data' is base64_encode on set but not decoded when get from $email
		//$this->assertEquals($emailReconstructed->attachments(),$Email->attachments());

		//$this->assertEquals($Email, $output['settings']);
	}

}
