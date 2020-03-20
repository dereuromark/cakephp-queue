<?php

namespace Queue\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\Mailer\Mailer;
use Cake\TestSuite\TestCase;
use Queue\Shell\Task\QueueEmailTask;
use Shim\TestSuite\ConsoleOutput;
use Shim\TestSuite\TestTrait;
use TestApp\Mailer\TestMailer;

class QueueEmailTaskTest extends TestCase {

	use TestTrait;

	/**
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * @var \Queue\Shell\Task\QueueEmailTask|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $Task;

	/**
	 * @var \Shim\TestSuite\ConsoleOutput
	 */
	protected $out;

	/**
	 * @var \Shim\TestSuite\ConsoleOutput
	 */
	protected $err;

	/**
	 * Setup Defaults
	 *
	 * @return void
	 */
	public function setUp(): void {
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

		$this->Task->run(['settings' => $settings, 'content' => 'Foo Bar'], 0);

		$this->assertInstanceOf(Mailer::class, $this->Task->mailer);

		$debugEmail = $this->Task->mailer;

		$transportConfig = $debugEmail->getTransport()->getConfig();
		$this->assertSame('Debug', $transportConfig['className']);
	}

	/**
	 * @return void
	 */
	public function testRunToolsEmailObject() {
		$mailer = new TestMailer();
		$mailer->setFrom('test@test.de');
		$mailer->setTo('test@test.de');

		Configure::write('Config.live', true);

		$data = [
			'settings' => $mailer,
			'content' => 'Foo Bar',
		];

		$this->Task->run($data, 0);

		$this->assertInstanceOf(TestMailer::class, $this->Task->mailer);

		/** @var \App\Mailer\TestMailer $testMailer */
		$testMailer = $this->Task->mailer;
		//$this->assertNull($debugEmail->getError());

		$transportConfig = $testMailer->getTransport()->getConfig();
		$this->assertSame('Debug', $transportConfig['className']);

		//$result = $testMailer->debug();
		//$this->assertTextContains('Foo Bar', $result['message']);
	}

}
