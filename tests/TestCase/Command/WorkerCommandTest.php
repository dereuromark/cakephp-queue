<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \Queue\Command\WorkerCommand
 */
class WorkerCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.Queue.QueueProcesses',
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
		$this->exec('queue worker');

		$output = $this->_out->output();
		$this->assertStringContainsString('Please use with [action] [PID] added', $output);
		$this->assertExitCode(1);
	}

}
