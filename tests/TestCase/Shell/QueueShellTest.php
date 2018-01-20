<?php

namespace Queue\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use Queue\Shell\QueueShell;
use Tools\TestSuite\ConsoleOutput;

class QueueShellTest extends TestCase {

	/**
	 * @var \Queue\Shell\QueueShell|\PHPUnit_Framework_MockObject_MockObject
	 */
	public $QueueShell;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	public $out;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	public $err;

	/**
	 * Fixtures to load
	 *
	 * @var array
	 */
	public $fixtures = [
		'plugin.Queue.QueuedJobs',
		'plugin.Queue.QueueProcesses',
	];

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

		$this->QueueShell = $this->getMockBuilder(QueueShell::class)
			->setMethods(['in', 'err', '_stop'])
			->setConstructorArgs([$io])
			->getMock();

		$this->QueueShell->initialize();

		Configure::write('Queue', [
			'sleeptime' => 2,
			'gcprob' => 10,
			'defaultworkertimeout' => 3,
			'defaultworkerretries' => 1,
			'workermaxruntime' => 5,
			'cleanuptimeout' => 10,
			'exitwhennothingtodo' => false,
			'pidfilepath' => false, // TMP . 'queue' . DS,
			'log' => false,
		]);
	}

	/**
	 * @return void
	 */
	public function testObject() {
		$this->assertTrue(is_object($this->QueueShell));
		$this->assertInstanceOf(QueueShell::class, $this->QueueShell);
	}

	/**
	 * @return void
	 */
	public function testStats() {
		$this->_needsConnection();

		$this->QueueShell->stats();
		$this->assertContains('Total unfinished Jobs      : 0', $this->out->output());
	}

	/**
	 * @return void
	 */
	public function testSettings() {
		$this->QueueShell->settings();
		$this->assertContains('* cleanuptimeout: 10', $this->out->output());
	}

	/**
	 * @return void
	 */
	public function testAddInexistent() {
		$this->QueueShell->args[] = 'FooBar';
		$this->QueueShell->add();
		$this->assertContains('Error: Task not found: FooBar', $this->out->output());
	}

	/**
	 * @return void
	 */
	public function testAdd() {
		$this->QueueShell->args[] = 'Example';
		$this->QueueShell->add();

		$this->assertContains('OK, job created, now run the worker', $this->out->output(), print_r($this->out->output, true));
	}

	/**
	 * @return void
	 */
	public function testRetry() {
		$this->_needsConnection();

		$this->QueueShell->args[] = 'RetryExample';
		$this->QueueShell->add();

		$expected = 'This is a very simple example of a QueueTask and how retries work';
		$this->assertContains($expected, $this->out->output());

		$this->QueueShell->runworker();

		$this->assertContains('Job did not finish, requeued after try 1.', $this->out->output());
	}

	/**
	 * Helper method for skipping tests that need a real connection.
	 *
	 * @return void
	 */
	protected function _needsConnection() {
		$config = ConnectionManager::config('test');
		$this->skipIf(strpos($config['driver'], 'Mysql') === false, 'Only Mysql is working yet for this.');
	}

}
