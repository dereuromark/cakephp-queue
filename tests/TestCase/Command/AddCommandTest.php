<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \Queue\Command\AddCommand
 */
class AddCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		//$this->useCommandRunner();
		$this->loadPlugins(['Queue']);
	}

	/**
	 * @return void
	 */
	public function testExecute(): void {
		$this->exec('queue add');

		$output = $this->_out->output();
		$this->assertStringContainsString('11 tasks available:', $output);
		$this->assertExitCode(0);
	}

	/**
	 * @return void
	 */
	public function testExecuteAddExample(): void {
		$this->exec('queue add Queue.Example');

		$output = $this->_out->output();
		$this->assertStringContainsString('OK, job created, now run the worker', $output);
		$this->assertExitCode(0);
	}

}
