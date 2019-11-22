<?php

namespace Queue\Test\TestCase\Shell;

use App\Mailer\TestEmail;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\Mailer\Email;
use Cake\TestSuite\TestCase;
use Queue\Shell\Task\QueueEmailTask;
use Tools\TestSuite\ConsoleOutput;
use Tools\TestSuite\ToolsTestTrait;

class QueueEmailTaskTest extends TestCase {

	use ToolsTestTrait;

	/**
	 * @var array
	 */
	public $fixtures = [
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * @var \Queue\Shell\Task\QueueEmailTask|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $Task;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	protected $out;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	protected $err;

	/**
	 * Setup Defaults
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$io = new ConsoleIo($this->out, $this->err);

		$this->Task = new QueueEmailTask($io);
	}

	/**
	 * @return void
	 */
	public function testRunArray() {
		$settings = [
			'from' => 'test@test.de',
			'to' => 'test@test.de',
		];

		$this->Task->run(['settings' => $settings, 'content' => 'Foo Bar'], null);

		$this->assertInstanceOf(Email::class, $this->Task->Email);

		$debugEmail = $this->Task->Email;

		$transportConfig = $debugEmail->getTransport()->getConfig();
		$this->assertSame('Debug', $transportConfig['className']);
	}

	/**
	 * @return void
	 */
	public function testRunToolsEmailObject() {
		$email = new TestEmail();
		$email->setFrom('test@test.de');
		$email->setTo('test@test.de');

		Configure::write('Config.live', true);

		$this->Task->run(['settings' => $email, 'content' => 'Foo Bar'], null);

		$this->assertInstanceOf(TestEmail::class, $this->Task->Email);

		/** @var \App\Mailer\TestEmail $debugEmail */
		$debugEmail = $this->Task->Email;
		$this->assertNull($debugEmail->getError());

		$transportConfig = $debugEmail->getTransport()->getConfig();
		$this->assertSame('Debug', $transportConfig['className']);

		$result = $debugEmail->debug();
		$this->assertTextContains('Foo Bar', $result['message']);
	}

}
