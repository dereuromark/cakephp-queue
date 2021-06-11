<?php
declare(strict_types = 1);

namespace Queue\Test\TestCase\Command;

use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \Queue\Command\AddCommand
 */
class AddCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @var string[]
	 */
	protected $fixtures = [
		//'plugin.Queue.QueueProcesses',
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->useCommandRunner();
	}

	/**
	 * @return void
	 */
	public function testExecute(): void {
		$this->exec('queue add');

		$output = $this->_out->output();
		$this->assertStringContainsString('10 tasks available:', $output);
	}

}
