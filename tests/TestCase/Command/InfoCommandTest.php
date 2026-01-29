<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

/**
 * @uses \Queue\Command\InfoCommand
 */
class InfoCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.Queue.QueueProcesses',
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->loadPlugins(['Queue']);
	}

	/**
	 * @return void
	 */
	public function testExecute(): void {
		$this->exec('queue info');

		$output = $this->_out->output();
		$this->assertStringContainsString('15 tasks available:', $output);
		$this->assertExitCode(0);
	}

	/**
	 * Test that new config names are displayed in the info command.
	 *
	 * @return void
	 */
	public function testExecuteWithNewConfigNames(): void {
		// Set up some config values using old names
		Configure::write('Queue.defaultworkertimeout', 300);
		Configure::write('Queue.workermaxruntime', 60);
		Configure::write('Queue.defaultworkerretries', 2);
		Configure::write('Queue.workertimeout', 120);

		// Suppress deprecation warnings triggered by Config methods reading old keys
		$errorLevel = error_reporting();
		error_reporting($errorLevel & ~E_USER_DEPRECATED);

		$this->exec('queue info');

		error_reporting($errorLevel);

		$output = $this->_out->output();

		// Check that new config names are displayed with old names noted
		$this->assertStringContainsString('defaultRequeueTimeout (was: defaultworkertimeout): 300', $output);
		$this->assertStringContainsString('workerLifetime (was: workermaxruntime): 60', $output);
		$this->assertStringContainsString('defaultJobRetries (was: defaultworkerretries): 2', $output);
		$this->assertStringContainsString('workerPhpTimeout (was: workertimeout): 120', $output);
		$this->assertExitCode(0);
	}

}
