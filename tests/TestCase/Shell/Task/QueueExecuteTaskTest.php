<?php

namespace Queue\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Exception;
use Queue\Shell\Task\QueueExecuteTask;
use Tools\TestSuite\ConsoleOutput;
use Tools\TestSuite\ToolsTestTrait;

class QueueExecuteTaskTest extends TestCase {

	use ToolsTestTrait;

	/**
	 * @var array
	 */
	public $fixtures = [
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * @var \Queue\Shell\Task\QueueExecuteTask|\PHPUnit\Framework\MockObject\MockObject
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

		$this->Task = new QueueExecuteTask($io);
	}

	/**
	 * @return void
	 */
	public function testRun() {
		$this->Task->run(['command' => 'php -v'], null);

		$this->assertTextContains('PHP ', $this->out->output());
	}

	/**
	 * @return void
	 */
	public function testRunFailureWithRedirect() {
		$exception = null;
		try {
			$this->Task->run(['command' => 'fooooobbbaraar -eeee'], null);
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
		$this->Task->run(['command' => 'fooooobbbaraar -eeee', 'accepted' => []], null);

		$this->assertTextContains('Success (code 127)', $this->out->output());
		$this->assertTextContains('fooooobbbaraar: not found', $this->out->output());
	}

	/**
	 * @return void
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Failed with error code 127: `fooooobbbaraar -eeee`
	 */
	public function testRunFailureWithoutRedirect() {
		$this->skipIf((bool)getenv('TRAVIS'), 'Not redirecting stderr to stdout prints noise to the CLI output in between test runs.');

		$this->Task->run(['command' => 'fooooobbbaraar -eeee', 'redirect' => false], null);
	}

}
