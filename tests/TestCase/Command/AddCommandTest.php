<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Command;

use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\Http\ServerRequest;
use Cake\TestSuite\Fixture\SchemaLoader;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\NullLogger;
use Queue\Command\AddCommand;
use Queue\Console\Io;
use Queue\Controller\Admin\QueueAppController;
use Queue\Model\Table\QueuedJobsTable;
use Queue\Queue\Processor;
use RuntimeException;

#[UsesClass(AddCommand::class)]
class AddCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.Queue.QueuedJobs',
		'plugin.Queue.QueueProcesses',
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
		$this->assertSame(0, (int)ConnectionManager::get('default')->execute('SELECT COUNT(*) FROM queued_jobs')->fetchColumn(0));
		$this->assertSame('queue_add_test', $this->fetchTable('Queue.QueuedJobs')->getConnection()->configName());
		$this->assertSame(1, (int)ConnectionManager::get('queue_add_test')->execute('SELECT COUNT(*) FROM queued_jobs')->fetchColumn(0));
	}

	public function testProcessorPreservesTableConnectionAndHonorsExplicitDefault(): void {
		$this->createSecondaryConnection();
		$secondary = ConnectionManager::get('queue_add_test');
		$jobs = $this->fetchTable('Queue.QueuedJobs');
		$processes = $this->fetchTable('Queue.QueueProcesses');
		$jobs->setConnection($secondary);
		$processes->setConnection($secondary);
		new Processor(new Io(new ConsoleIo()), new NullLogger());
		$this->assertSame($secondary, $jobs->getConnection());
		$this->assertSame($secondary, $processes->getConnection());

		new Processor(new Io(new ConsoleIo()), new NullLogger(), null, 'default');
		$this->assertSame(ConnectionManager::get('default'), $jobs->getConnection());
		$this->assertSame(ConnectionManager::get('default'), $processes->getConnection());
	}

	public function testJobViewHonorsExplicitDefault(): void {
		$this->createSecondaryConnection();
		$defaultJobs = new QueuedJobsTable([
			'alias' => 'DefaultJobs',
			'connection' => ConnectionManager::get('default'),
		]);
		$job = $defaultJobs->createJob('Queue.Example', ['message' => 'Primary proof']);
		$this->fetchTable('Queue.QueuedJobs')->setConnection(ConnectionManager::get('queue_add_test'));

		$this->exec('queue job view ' . $job->id . ' --connection default');

		$this->assertExitCode(0);
		$this->assertOutputContains('Primary proof');
	}

	public function testAdminUsesLegacyQueueConnection(): void {
		$this->createSecondaryConnection();
		Configure::write('Queue.connection', 'queue_add_test');
		$controller = new QueueAppController(new ServerRequest());

		$this->assertSame('queue_add_test', $controller->viewBuilder()->getVar('queueActiveConnection'));
	}

	#[DataProvider('selectedConnectionModes')]
	public function testInfoReadsSelectedConnection(string $option, bool $legacyDefault): void {
		$this->createSecondaryConnection();
		$table = new QueuedJobsTable([
			'alias' => 'SecondaryJobs',
			'connection' => ConnectionManager::get('queue_add_test'),
		]);
		$table->createJob('Queue.Example', ['message' => 'Secondary']);

		$this->fetchTable('Queue.QueuedJobs')->setConnection(ConnectionManager::get('queue_add_test'));
		if ($legacyDefault) {
			Configure::write('Queue.connection', 'default');
		}
		$this->exec('queue info --format json' . $option);

		$this->assertExitCode(0);
		$info = json_decode($this->_out->output(), true, flags: JSON_THROW_ON_ERROR);
		$this->assertSame(1, $info['jobs']['unfinished']);
	}

	#[DataProvider('selectedConnectionModes')]
	public function testWorkerEndTargetsSelectedConnection(string $option, bool $legacyDefault): void {
		$this->createSecondaryConnection();
		$this->fetchTable('Queue.QueueProcesses')->updateAll(['terminate' => false], []);
		$connection = ConnectionManager::get('queue_add_test');
		$connection->execute("INSERT INTO queue_processes (pid, workerkey, created, modified, terminate) VALUES ('secondary', 'secondary', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 0)");

		$this->fetchTable('Queue.QueuedJobs')->setConnection($connection);
		if ($legacyDefault) {
			Configure::write('Queue.connection', 'default');
		}
		$this->exec('queue worker end all' . $option);

		$this->assertExitCode(0);
		$this->assertSame(1, (int)$connection->execute('SELECT terminate FROM queue_processes')->fetchColumn(0));
		$this->assertSame(0, (int)ConnectionManager::get('default')->execute('SELECT terminate FROM queue_processes')->fetchColumn(0));
	}

	public function testAddPreservesPreconfiguredTableConnection(): void {
		$this->createSecondaryConnection();
		Configure::write('Queue.connection', 'default');
		$this->fetchTable('Queue.QueuedJobs')->setConnection(ConnectionManager::get('queue_add_test'));

		$this->exec('queue add Queue.Example');

		$this->assertExitCode(0);
		$this->assertSame(1, (int)ConnectionManager::get('queue_add_test')->execute('SELECT COUNT(*) FROM queued_jobs')->fetchColumn(0));
		$this->assertSame(0, (int)ConnectionManager::get('default')->execute('SELECT COUNT(*) FROM queued_jobs')->fetchColumn(0));
	}

	public function testExplicitConnectionWithoutWhitelist(): void {
		$this->createSecondaryConnection();

		$this->exec('queue add Queue.Example --connection queue_add_test');

		$this->assertExitCode(0);
		$this->assertSame(1, (int)ConnectionManager::get('queue_add_test')->execute('SELECT COUNT(*) FROM queued_jobs')->fetchColumn(0));
		$this->assertSame(0, (int)ConnectionManager::get('default')->execute('SELECT COUNT(*) FROM queued_jobs')->fetchColumn(0));
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

	/**
	 * @return array<string, array{string, bool}>
	 */
	public static function selectedConnectionModes(): array {
		return [
			'explicit' => [' --connection queue_add_test', false],
			'table override' => ['', false],
			'table override with legacy default' => ['', true],
		];
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
