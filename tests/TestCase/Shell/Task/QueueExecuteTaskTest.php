<?php

namespace Queue\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
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
	 * @var \Queue\Shell\Task\QueueExecuteTask|\PHPUnit_Framework_MockObject_MockObject
	 */
	public $Task;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	public $out;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	public $err;

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
		$result = $this->Task->run(['command' => 'php -v'], null);

		$this->assertTrue($result);

		$this->assertTextContains('PHP ', $this->out->output());
	}

	/**
	 * @return void
	 */
	public function testRunFailureWithRedirect() {
		$result = $this->Task->run(['command' => 'fooooobbbaraar -eeee'], null);

		$this->assertFalse($result);

		$this->assertTextContains('Error (code 127)', $this->err->output());
		$this->assertTextContains('fooooobbbaraar: not found', $this->out->output());
	}

	/**
	 * @return void
	 */
	public function testRunFailureWithRedirectAndIgnoreCode() {
		$result = $this->Task->run(['command' => 'fooooobbbaraar -eeee', 'accepted' => []], null);

		$this->assertTrue($result);

		$this->assertTextContains('Success (code 127)', $this->out->output());
		$this->assertTextContains('fooooobbbaraar: not found', $this->out->output());
	}

	/**
	 * @return void
	 */
	public function testRunFailureWithoutRedirect() {
		$this->skipIf((bool)getenv('TRAVIS'), 'Not redirecting stderr to stdout prints noise to the CLI output in between test runs.');

		$result = $this->Task->run(['command' => 'fooooobbbaraar -eeee', 'redirect' => false], null);

		$this->assertFalse($result);

		$this->assertTextContains('Error (code 127)', $this->err->output());
		$this->assertTextNotContains('fooooobbbaraar: not found', $this->err->output());
	}

}
