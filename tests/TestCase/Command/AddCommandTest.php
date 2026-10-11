<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\SchemaLoader;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use Queue\Command\AddCommand;
use RuntimeException;

#[UsesClass(AddCommand::class)]
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

	public function testAddToExplicitConnection(): void {
		$this->createSecondaryConnection();
		Configure::write('Queue.connections', ['default', 'queue_add_test']);
		$table = $this->fetchTable('Queue.QueuedJobs');
		$previousConnection = $table->getConnection();

		$this->exec('queue add Queue.Example --connection queue_add_test');

		$this->assertExitCode(0);
		$this->assertSame(0, $table->find()->count());
		$this->assertSame(1, (int)ConnectionManager::get('queue_add_test')->execute('SELECT COUNT(*) FROM queued_jobs')->fetchColumn(0));
		$this->assertSame($previousConnection, $table->getConnection());
	}

	public function testAddUsesFirstConfiguredConnection(): void {
		$this->createSecondaryConnection();
		Configure::write('Queue.connections', ['queue_add_test', 'default']);

		$this->exec('queue add Queue.Example');

		$this->assertExitCode(0);
		$this->assertSame(0, $this->fetchTable('Queue.QueuedJobs')->find()->count());
		$this->assertSame(1, (int)ConnectionManager::get('queue_add_test')->execute('SELECT COUNT(*) FROM queued_jobs')->fetchColumn(0));
	}

	public function testAddPreservesLegacyQueueConnection(): void {
		$this->createSecondaryConnection();
		Configure::write('Queue.connection', 'queue_add_test');

		$this->exec('queue add Queue.Example');

		$this->assertExitCode(0);
		$this->assertSame(1, (int)ConnectionManager::get('queue_add_test')->execute('SELECT COUNT(*) FROM queued_jobs')->fetchColumn(0));
		$this->assertSame(0, (int)ConnectionManager::get('default')->execute('SELECT COUNT(*) FROM queued_jobs')->fetchColumn(0));
	}

	public function testExplicitDefaultOverridesLegacyQueueConnection(): void {
		$this->createSecondaryConnection();
		Configure::write('Queue.connection', 'queue_add_test');

		$this->exec('queue add Queue.Example --connection default');

		$this->assertExitCode(0);
		$this->assertSame(0, (int)ConnectionManager::get('queue_add_test')->execute('SELECT COUNT(*) FROM queued_jobs')->fetchColumn(0));
		$this->assertSame(1, (int)ConnectionManager::get('default')->execute('SELECT COUNT(*) FROM queued_jobs')->fetchColumn(0));
	}

	public function testAddRejectsConnectionOutsideWhitelist(): void {
		Configure::write('Queue.connections', ['default', 'queue_add_test']);
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Invalid connection: not_allowed');

		$this->exec('queue add Queue.Example --connection not_allowed');
	}

	protected function createSecondaryConnection(): void {
		$this->skipIf(!extension_loaded('pdo_sqlite'), 'Requires SQLite for an isolated second database.');
		ConnectionManager::setConfig('queue_add_test', [
			'className' => Connection::class,
			'driver' => Sqlite::class,
			'database' => ':memory:',
		]);
		(new SchemaLoader())->loadInternalFile(TESTS . 'schema.php', 'queue_add_test');
	}

	public function tearDown(): void {
		ConnectionManager::drop('queue_add_test');
		Configure::delete('Queue.connections');
		Configure::delete('Queue.connection');

		parent::tearDown();
	}

}
