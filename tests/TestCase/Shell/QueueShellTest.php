<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
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
	protected array $fixtures = [
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

		$this->skipIf(true);

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
	 * //FIXME: Migrate to worker test
	 *
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
