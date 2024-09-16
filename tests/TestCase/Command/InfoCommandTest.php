<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
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

}
