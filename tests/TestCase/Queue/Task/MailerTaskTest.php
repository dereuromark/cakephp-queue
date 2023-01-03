<?php

namespace Queue\Test\TestCase\Queue\Task;

use Cake\Console\ConsoleIo;
use Cake\Datasource\ConnectionManager;
use Cake\Mailer\Exception\MissingMailerException;
use Cake\TestSuite\TestCase;
use Queue\Console\Io;
use Queue\Model\QueueException;
use Queue\Queue\Task\MailerTask;
use Shim\TestSuite\ConsoleOutput;
use Shim\TestSuite\TestTrait;
use TestApp\Mailer\TestMailer;

class MailerTaskTest extends TestCase {

	use TestTrait;

	/**
	 * @var array
	 */
	protected $fixtures = [
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
		$this->_skipPostgres();

		$this->Task->run([
			'mailer' => TestMailer::class,
			'action' => 'testAction',
			'vars' => [true],
		], 0);

		$this->assertInstanceOf(TestMailer::class, $this->Task->mailer);

		/** @var \TestApp\Mailer\TestMailer $testMailer */
		$testMailer = $this->Task->mailer;

		$transportConfig = $testMailer->getTransport()->getConfig();
		$this->assertSame('Debug', $transportConfig['className']);

		$result = $testMailer->getDebug();
		$this->assertTextContains('bool(true)', $result['message']);
	}

	/**
	 * @return void
	 */
	public function testRunUnkownMailerException() {
		$this->_skipPostgres();

		$this->expectException(MissingMailerException::class);
		$this->Task->run([
			'mailer' => 'UnknownMailer',
		], 0);
	}

	/**
	 * @return void
	 */
	public function testRunNoMailerException() {
		$this->_skipPostgres();

		$this->expectException(QueueException::class);
		$this->expectExceptionMessage('Queue Mailer task called without valid mailer class.');
		$this->Task->run([
			'action' => 'testAction',
			'vars' => [true],
		], 0);
	}

	/**
	 * Helper method for skipping tests that need a non Postgres connection.
	 *
	 * @return void
	 */
	protected function _skipPostgres() {
		$config = ConnectionManager::getConfig('test');
		$skip = strpos($config['driver'], 'Postgres') !== false;
		$this->skipIf($skip, 'Only non Postgres is working yet for this.');
	}

}
