<?php

namespace Queue\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Exception;
use Queue\Shell\Task\QueueExecuteTask;
use RuntimeException;
use Shim\TestSuite\ConsoleOutput;
use Shim\TestSuite\TestTrait;

class QueueExecuteTaskTest extends TestCase {

	use TestTrait;

	/**
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * @var \Queue\Shell\Task\QueueExecuteTask|\PHPUnit\Framework\MockObject\MockObject
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

		$this->Task = new QueueExecuteTask($io);
	}

	/**
	 * @return void
	 */
	public function testRun() {
		$this->Task->run(['command' => 'php -v'], 0);

		$this->assertTextContains('PHP ', $this->out->output());
	}

	/**
	 * @return void
	 */
	public function testRunFailureWithRedirect() {
		$exception = null;
		try {
			$this->Task->run(['command' => 'fooooobbbaraar -eeee'], 0);
		} catch (\Exception $e) {
			$exception = $e;
		}

		$this->assertInstanceOf(Exception::class, $e);
		$this->assertSame('Failed with error code 127: `fooooobbbaraar -eeee`', $e->getMessage());

		$this->assertTextContains('Error (code 127)', $this->err->output());
		$this->assertTextContains('fooooobbbaraar: not found', $this->out->output());
	}

	/**
	 * @return void
	 */
	public function testRunFailureWithRedirectAndIgnoreCode() {
		$this->Task->run(['command' => 'fooooobbbaraar -eeee', 'accepted' => []], 0);

		$this->assertTextContains('Success (code 127)', $this->out->output());
		$this->assertTextContains('fooooobbbaraar: not found', $this->out->output());
	}

	/**
	 * @return void
	 */
	public function testRunFailureWithoutRedirect() {
		$this->skipIf((bool)getenv('TRAVIS'), 'Not redirecting stderr to stdout prints noise to the CLI output in between test runs.');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Failed with error code 127: `fooooobbbaraar -eeee`');

		$this->Task->run(['command' => 'fooooobbbaraar -eeee', 'redirect' => false], 0);
	}

}
