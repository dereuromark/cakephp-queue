<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Queue\Task;

use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Exception;
use Queue\Console\Io;
use Queue\Model\QueueException;
use Queue\Queue\Task\ExecuteTask;
use RuntimeException;
use Shim\TestSuite\ConsoleOutput;
use Shim\TestSuite\TestTrait;

class ExecuteTaskTest extends TestCase {

	use TestTrait;

	/**
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * @var \Queue\Queue\Task\ExecuteTask|\PHPUnit\Framework\MockObject\MockObject
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

		$this->Task = new ExecuteTask($io);
	}

	/**
	 * @return void
	 */
	public function testRun() {
		$this->Task->run(['command' => 'php', 'params' => ['-v']], 0);

		$this->assertTextContains('PHP ', $this->out->output());
	}

	/**
	 * @return void
	 */
	public function testRunFailureWithRedirect() {
		$exception = null;
		try {
			$this->Task->run(['command' => 'fooooobbbaraar', 'params' => ['-eeee']], 0);
		} catch (Exception $e) {
			$exception = $e;
		}

		$this->assertInstanceOf(Exception::class, $exception);
		$this->assertSame("Failed with error code 127: `'fooooobbbaraar' '-eeee' 2>&1`", $exception->getMessage());

		$this->assertTextContains('Error (code 127)', $this->err->output());
		$this->assertTextContains('fooooobbbaraar: not found', $this->out->output());
	}

	/**
	 * @return void
	 */
	public function testRunFailureWithRedirectAndIgnoreCode() {
		$this->Task->run(['command' => 'fooooobbbaraar', 'params' => ['-eeee'], 'accepted' => []], 0);

		$this->assertTextContains('Success (code 127)', $this->out->output());
		$this->assertTextContains('fooooobbbaraar: not found', $this->out->output());
	}

	/**
	 * @return void
	 */
	public function testRunFailureWithoutRedirect() {
		$this->skipIf((bool)getenv('TRAVIS'), 'Not redirecting stderr to stdout prints noise to the CLI output in between test runs.');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Failed with error code 127: `'fooooobbbaraar' '-eeee'`");

		$this->Task->run(['command' => 'fooooobbbaraar', 'params' => ['-eeee'], 'redirect' => false], 0);
	}

	/**
	 * Per-token escapeshellarg neutralizes argument-injection payloads instead of
	 * letting them re-tokenize against the shell. The rogue param has to be quoted as
	 * a single argument and must not be interpreted as additional flags.
	 *
	 * @return void
	 */
	public function testRunEscapesArgumentInjectionPayloadAsSingleToken() {
		$exception = null;
		try {
			$this->Task->run([
				'command' => 'fooooobbbaraar',
				'params' => ['-r --some-flag /etc/passwd'],
			], 0);
		} catch (Exception $e) {
			$exception = $e;
		}

		$this->assertInstanceOf(Exception::class, $exception);
		$this->assertStringContainsString(
			"'fooooobbbaraar' '-r --some-flag /etc/passwd'",
			(string)$exception->getMessage(),
		);
	}

	/**
	 * In production (debug=false) the command MUST be in the allow-list.
	 * Without it, the task throws before any exec() happens.
	 *
	 * @return void
	 */
	public function testRunRejectsCommandNotInAllowListWhenDebugDisabled() {
		Configure::write('debug', false);
		Configure::write('Queue.executeAllowedCommands', ['php']);

		$this->expectException(QueueException::class);
		$this->expectExceptionMessage('Command `rm` is not in Queue.executeAllowedCommands allow-list');

		try {
			$this->Task->run(['command' => 'rm', 'params' => ['-rf', '/tmp/x']], 0);
		} finally {
			Configure::write('debug', true);
			Configure::delete('Queue.executeAllowedCommands');
		}
	}

	/**
	 * In production (debug=false) the empty/missing allow-list MUST reject every command.
	 *
	 * @return void
	 */
	public function testRunRejectsAnyCommandWhenAllowListEmptyAndDebugDisabled() {
		Configure::write('debug', false);
		Configure::delete('Queue.executeAllowedCommands');

		$this->expectException(QueueException::class);
		$this->expectExceptionMessage('Command `php` is not in Queue.executeAllowedCommands allow-list');

		try {
			$this->Task->run(['command' => 'php', 'params' => ['-v']], 0);
		} finally {
			Configure::write('debug', true);
		}
	}

	/**
	 * In production with the command listed, the task runs as normal.
	 *
	 * @return void
	 */
	public function testRunPassesWhenCommandInAllowListAndDebugDisabled() {
		Configure::write('debug', false);
		Configure::write('Queue.executeAllowedCommands', ['php']);

		try {
			$this->Task->run(['command' => 'php', 'params' => ['-v']], 0);
		} finally {
			Configure::write('debug', true);
			Configure::delete('Queue.executeAllowedCommands');
		}

		$this->assertTextContains('PHP ', $this->out->output());
	}

}
