<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Queue\Task;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Queue\Console\Io;
use Queue\Model\QueueException;
use Queue\Queue\Task\MailerTask;
use ReflectionClass;
use Shim\TestSuite\ConsoleOutput;
use Shim\TestSuite\TestTrait;
use TestApp\Mailer\TestMailer;

class MailerTaskTest extends TestCase {

	use TestTrait;

	/**
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * @var \Queue\Queue\Task\MailerTask|\PHPUnit\Framework\MockObject\MockObject
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
		$io = new Io(new ConsoleIo($this->out, $this->err));

		$this->Task = new MailerTask($io);
	}

	/**
	 * @return void
	 */
	public function testRunToolsMailerConfig() {
		$this->Task->run([
			'class' => TestMailer::class,
			'action' => 'testAction',
			'vars' => [true],
		], 0);

		$reflection = new ReflectionClass($this->Task);
		$property = $reflection->getProperty('mailer');
		$property->setAccessible(true);
		$mailer = $property->getValue($this->Task);

		$this->assertInstanceOf(TestMailer::class, $mailer);

		$transportConfig = $mailer->getTransport()->getConfig();
		$this->assertSame('Debug', $transportConfig['className']);

		$result = $mailer->getDebug();
		$this->assertTextContains('bool(true)', $result['message']);
	}

	/**
	 * @return void
	 */
	public function testRunMissingMailerException() {
		$this->expectException(QueueException::class);
		$this->expectExceptionMessage('Queue Mailer task called without valid `mailer` class.');

		$this->Task->run([], 0);
	}

	/**
	 * @return void
	 */
	public function testRunMissingActionException() {
		$this->expectException(QueueException::class);
		$this->expectExceptionMessage('Queue Mailer task called without `action` data.');

		$this->Task->run([
			'class' => TestMailer::class,
		], 0);
	}

}
