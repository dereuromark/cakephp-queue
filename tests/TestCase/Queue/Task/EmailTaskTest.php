<?php

namespace Queue\Test\TestCase\Queue\Task;

use Cake\Console\ConsoleIo;
use Cake\Datasource\ConnectionManager;
use Cake\Mailer\Mailer;
use Cake\TestSuite\TestCase;
use Queue\Console\Io;
use Queue\Queue\Task\EmailTask;
use Shim\TestSuite\ConsoleOutput;
use Shim\TestSuite\TestTrait;
use TestApp\Mailer\TestMailer;

class EmailTaskTest extends TestCase {

	use TestTrait;

	/**
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * @var \Queue\Queue\Task\EmailTask|\PHPUnit\Framework\MockObject\MockObject
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

		$this->Task = new EmailTask($io);
	}

	/**
	 * @return void
	 */
	public function testRunArray() {
		$settings = [
			'from' => 'test@test.de',
			'to' => 'test@test.de',
		];

		$data = [
			'settings' => $settings,
			'content' => 'Foo Bar',
		];
		$this->Task->run($data, 0);

		$this->assertInstanceOf(Mailer::class, $this->Task->mailer);

		$debugEmail = $this->Task->mailer;

		$transportConfig = $debugEmail->getTransport()->getConfig();
		$this->assertSame('Debug', $transportConfig['className']);
	}

	/**
	 * @return void
	 */
	public function testRunToolsEmailObject() {
		$this->_skipPostgres();

		$mailer = new TestMailer();
		$mailer->setFrom('test@test.de');
		$mailer->setTo('test@test.de');

		/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
		$queuedJobsTable = $this->getTableLocator()->get('Queue.QueuedJobs');
		$queuedJobsTable->createJob('Queue.Email', ['settings' => $mailer]);

		$queuedJob = $queuedJobsTable->find()->orderDesc('id')->firstOrFail();
		$data = unserialize($queuedJob->data);
		/** @var \TestApp\Mailer\TestMailer $mailer */
		$mailer = $data['settings'];

		$data = [
			'settings' => $mailer,
			'content' => 'Foo Bar',
		];

		$this->Task->run($data, 0);

		$this->assertInstanceOf(TestMailer::class, $this->Task->mailer);

		/** @var \TestApp\Mailer\TestMailer $testMailer */
		$testMailer = $this->Task->mailer;

		$transportConfig = $testMailer->getTransport()->getConfig();
		$this->assertSame('Debug', $transportConfig['className']);

		$result = $testMailer->getDebug();
		$this->assertTextContains('Foo Bar', $result['message']);
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
