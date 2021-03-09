<?php

namespace Queue\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Queue\Shell\QueueShell;
use Shim\TestSuite\ConsoleOutput;
use Shim\TestSuite\TestTrait;

class QueueShellTest extends TestCase {

	use TestTrait;

	/**
	 * @var \Queue\Shell\QueueShell|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $shell;

	/**
	 * @var \Shim\TestSuite\ConsoleOutput
	 */
	protected $out;

	/**
	 * @var \Shim\TestSuite\ConsoleOutput
	 */
	protected $err;

	/**
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Queue.QueuedJobs',
		'plugin.Queue.QueueProcesses',
	];

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

		$this->shell = $this->getMockBuilder(QueueShell::class)
			->setMethods(['in', 'err', '_stop'])
			->setConstructorArgs([$io])
			->getMock();

		$this->shell->initialize();

		Configure::write('Queue', [
			'sleeptime' => 2,
			'defaultworkertimeout' => 3,
			'workermaxruntime' => 5,
			'cleanuptimeout' => 10,
			'exitwhennothingtodo' => false,
			'log' => false,
		]);
	}

	/**
	 * @return void
	 */
	public function testObject() {
		$this->assertTrue(is_object($this->shell));
		$this->assertInstanceOf(QueueShell::class, $this->shell);
	}

	/**
	 * @return void
	 */
	public function testStats() {
		$this->_needsConnection();

		$this->shell->stats();
		$this->assertStringContainsString('Total unfinished jobs: 0', $this->out->output());
	}

	/**
	 * @return void
	 */
	public function testSettings() {
		$this->shell->settings();
		$this->assertStringContainsString('* cleanuptimeout: 10', $this->out->output());
	}

	/**
	 * @return void
	 */
	public function testAddInexistent() {
		$this->shell->args[] = 'FooBar';
		$this->shell->add();
		$this->assertStringContainsString('Error: Task not found: FooBar', $this->out->output());
	}

	/**
	 * @return void
	 */
	public function testAdd() {
		$this->shell->args[] = 'Example';
		$this->shell->add();

		$this->assertStringContainsString('OK, job created, now run the worker', $this->out->output(), print_r($this->out->output, true));
	}

	/**
	 * @return void
	 */
	public function testHardReset() {
		$this->shell->hardReset();

		$this->assertStringContainsString('OK', $this->out->output(), print_r($this->out->output, true));
	}

	/**
	 * @return void
	 */
	public function testHardResetIntegration() {
		/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
		$queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
		$queuedJobsTable->createJob('Example');

		$queuedJobs = $queuedJobsTable->find()->count();
		$this->assertSame(1, $queuedJobs);

		$this->shell->runCommand(['hard_reset']);

		$this->assertStringContainsString('OK', $this->out->output(), print_r($this->out->output, true));

		$queuedJobs = $queuedJobsTable->find()->count();
		$this->assertSame(0, $queuedJobs);
	}

	/**
	 * @return void
	 */
	public function testReset() {
		$this->shell->reset();

		$this->assertStringContainsString('0 jobs reset.', $this->out->output(), print_r($this->out->output, true));
	}

	/**
	 * @return void
	 */
	public function testRerun() {
		$this->shell->rerun('Foo');

		$this->assertStringContainsString('0 jobs reset for re-run.', $this->out->output(), print_r($this->out->output, true));
	}

	/**
	 * @return void
	 */
	public function testEnd() {
		$this->shell->end();

		$this->assertStringContainsString('No processes found', $this->out->output(), print_r($this->out->output, true));
	}

	/**
	 * @return void
	 */
	public function testKill() {
		$this->shell->kill();

		$this->assertStringContainsString('No processes found', $this->out->output(), print_r($this->out->output, true));
	}

	/**
	 * @return void
	 */
	public function testRetry() {
		$file = TMP . 'task_retry.txt';
		if (file_exists($file)) {
			unlink($file);
		}

		$this->_needsConnection();

		$this->shell->args[] = 'RetryExample';
		$this->shell->add();

		$expected = 'This is a very simple example of a QueueTask and how retries work';
		$this->assertStringContainsString($expected, $this->out->output());

		$this->shell->runworker();

		$this->assertStringContainsString('Job did not finish, requeued after try 1.', $this->out->output());
	}

	/**
	 * @return void
	 */
	public function testTimeNeeded() {
		$this->shell = $this->getMockBuilder(QueueShell::class)->setMethods(['_time'])->getMock();

		$first = time();
		$second = $first - HOUR + MINUTE;
		$this->shell->expects($this->at(0))->method('_time')->will($this->returnValue($first));
		$this->shell->expects($this->at(1))->method('_time')->will($this->returnValue($second));
		$this->shell->expects($this->exactly(2))->method('_time')->withAnyParameters();

		$result = $this->invokeMethod($this->shell, '_timeNeeded');
		$this->assertSame('3540s', $result);
	}

	/**
	 * @return void
	 */
	public function testMemoryUsage() {
		$result = $this->invokeMethod($this->shell, '_memoryUsage');
		$this->assertRegExp('/^\d+MB/', $result, 'Should be e.g. `17MB` or `17MB/1GB` etc.');
	}

	/**
	 * @return void
	 */
	public function testStringToArray() {
		$string = 'Foo,Bar,';
		$result = $this->invokeMethod($this->shell, '_stringToArray', [$string]);

		$expected = [
			'Foo',
			'Bar',
		];
		$this->assertSame($expected, $result);
	}

	/**
	 * Helper method for skipping tests that need a real connection.
	 *
	 * @return void
	 */
	protected function _needsConnection() {
		$config = ConnectionManager::getConfig('test');
		$skip = strpos($config['driver'], 'Mysql') === false && strpos($config['driver'], 'Postgres') === false;
		$this->skipIf($skip, 'Only Mysql/Postgres is working yet for this.');
	}

}
