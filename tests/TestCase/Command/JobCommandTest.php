<?php
declare(strict_types = 1);

namespace Queue\Test\TestCase\Command;

use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \Queue\Command\JobCommand
 */
class JobCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @var string[]
	 */
	protected $fixtures = [
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
		$this->exec('queue job');

		$output = $this->_out->output();
		$this->assertStringContainsString('Please use with [action] [ID] added', $output);
	}

}
