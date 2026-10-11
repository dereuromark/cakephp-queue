<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use Queue\Command\RunCommand;

#[UsesClass(RunCommand::class)]
class RunCommandTest extends TestCase {

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

		Configure::write('Queue', [
			'sleeptime' => 1,
			'defaultRequeueTimeout' => 180, // 3 minutes - higher than any task timeout
			'workerLifetime' => 3,
			'cleanuptimeout' => 10,
			'exitwhennothingtodo' => false,
		]);
	}

	/**
	 * @return void
	 */
	public function testExecute(): void {
		$this->_needsConnection();

		$this->exec('queue run');

		$output = $this->_out->output();
		// Worker loop ran to completion. The per-iteration heartbeat is
		// verbose-only now, so assert on the always-on termination event.
		$this->assertStringContainsString('terminating.', $output);
		$this->assertExitCode(0);
	}

	/**
	 * @return void
	 */
	public function testServiceInjection(): void {
		$this->_needsConnection();

		$this->exec('queue add Foo');
		$this->exec('queue run');

		$output = $this->_out->output();
		$this->assertStringContainsString('Running Job of type "Foo"', $output);
		$this->assertStringContainsString('CakePHP Foo Example.', $output);
		$this->assertStringContainsString('My TestService', $output);
		$this->assertExitCode(0);
	}

	public function testGettingStartedExample(): void {
		$this->_needsConnection();
		Configure::write('Queue.exitwhennothingtodo', true);

		$this->exec('queue add Queue.Example');
		$this->assertExitCode(0);
		$this->exec('queue run --max-runtime 20 --type Queue.Example -v');
		$this->assertExitCode(0);
		$this->assertOutputContains('CakePHP Queue Example task.');
		$this->assertOutputContains('Job Finished.');
		$job = $this->fetchTable('Queue.QueuedJobs')->find()->firstOrFail();
		$this->assertNotNull($job->completed);
	}

	/**
	 * Helper method for skipping tests that need a real connection.
	 *
	 * @return void
	 */
	protected function _needsConnection() {
		$config = ConnectionManager::getConfig('test');
		$skip = !str_contains((string)$config['driver'], 'Mysql') && !str_contains((string)$config['driver'], 'Postgres');
		$this->skipIf($skip, 'Only Mysql/Postgres is working yet for this.');
	}

}
