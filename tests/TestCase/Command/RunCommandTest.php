<?php
declare(strict_types = 1);

namespace Queue\Test\TestCase\Command;

use Cake\Core\Configure;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \Queue\Command\RunCommand
 */
class RunCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @var string[]
	 */
	protected $fixtures = [
		'plugin.Queue.QueueProcesses',
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Configure::write('Queue', [
			'sleeptime' => 1,
			'defaultworkertimeout' => 3,
			'workermaxruntime' => 3,
			'cleanuptimeout' => 10,
			'exitwhennothingtodo' => false,
		]);

		$this->useCommandRunner();
	}

	/**
	 * @return void
	 */
	public function testExecute(): void {
		$this->exec('queue run');

		$output = $this->_out->output();
		$this->assertStringContainsString('Looking for Job', $output);
	}

}
