<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * @uses \Queue\Command\RunCommand
 */
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
			'defaultworkertimeout' => 3,
			'workermaxruntime' => 3,
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
		$this->assertStringContainsString('Looking for Job', $output);
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
		$this->assertStringContainsString('Looking for Job', $output);
		$this->assertStringContainsString('CakePHP Foo Example.', $output);
		$this->assertStringContainsString('My TestService', $output);
		$this->assertExitCode(0);
	}

	/**
	 * Helper method for skipping tests that need a real connection.
	 *
	 * @return void
	 */
	protected function _needsConnection() {
		$config = ConnectionManager::getConfig('test');
		$skip = strpos($config['driver'], 'Mysql') === false && strpos($config['driver'], 'Postgres') === false;
		$this->skipIf($skip, 'Only Mysql/Postgres is working yet for this.');
	}

}
