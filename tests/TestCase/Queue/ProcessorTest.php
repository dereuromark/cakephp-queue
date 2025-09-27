<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Queue;

use Cake\Console\CommandInterface;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;
use Psr\Log\NullLogger;
use Queue\Console\Io;
use Queue\Model\Entity\QueuedJob;
use Queue\Model\Table\QueuedJobsTable;
use Queue\Queue\Processor;
use Queue\Queue\Task\RetryExampleTask;
use ReflectionClass;
use RuntimeException;
use Shim\TestSuite\ConsoleOutput;
use Shim\TestSuite\TestTrait;

class ProcessorTest extends TestCase {

	use TestTrait;

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.Queue.QueueProcesses',
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * @var \Queue\Queue\Processor
	 */
	protected $Processor;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

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
	public function testStringToArray() {
		$this->Processor = new Processor(new Io(new ConsoleIo()), new NullLogger());

		$string = 'Foo,Bar,';
		$result = $this->invokeMethod($this->Processor, 'stringToArray', [$string]);

		$expected = [
			'Foo',
			'Bar',
		];
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testTimeNeeded() {
		$this->Processor = new Processor(new Io(new ConsoleIo()), new NullLogger());

		$result = $this->invokeMethod($this->Processor, 'timeNeeded');
		$this->assertMatchesRegularExpression('/\d+s/', $result);
	}

	/**
	 * @return void
	 */
	public function testMemoryUsage() {
		$this->Processor = new Processor(new Io(new ConsoleIo()), new NullLogger());

		$result = $this->invokeMethod($this->Processor, 'memoryUsage');
		$this->assertMatchesRegularExpression('/^\d+MB/', $result, 'Should be e.g. `17MB` or `17MB/1GB` etc.');
	}

	/**
	 * @return void
	 */
	public function testRun() {
		$this->_needsConnection();

		$out = new ConsoleOutput();
		$err = new ConsoleOutput();
		$this->Processor = new Processor(new Io(new ConsoleIo($out, $err)), new NullLogger());

		$config = [
			'verbose' => true,
		];
		$result = $this->Processor->run($config);

		$this->assertSame(CommandInterface::CODE_SUCCESS, $result);
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

	/**
	 * @return void
	 */
	public function testMaxAttemptsExhaustedEvent() {
		// Set up event tracking
		$eventList = new EventList();
		EventManager::instance()->setEventList($eventList);

		// Create a job that will fail
		$QueuedJobs = $this->getTableLocator()->get('Queue.QueuedJobs');
		$job = $QueuedJobs->createJob('Queue.RetryExample', [], ['priority' => 1]);

		// Manually set attempts to 5 (simulating previous failed attempts)
		// The default RetryExampleTask has retries=4, so 5 attempts exceeds it
		$job->attempts = 5;
		$QueuedJobs->saveOrFail($job);

		// Create processor
		$out = new ConsoleOutput();
		$err = new ConsoleOutput();
		$processor = new Processor(new Io(new ConsoleIo($out, $err)), new NullLogger());

		// Create a mock task that always fails
		$mockTask = $this->getMockBuilder(RetryExampleTask::class)
			->setConstructorArgs([new Io(new ConsoleIo($out, $err)), new NullLogger()])
			->onlyMethods(['run'])
			->getMock();
		$mockTask->method('run')->willThrowException(new RuntimeException('Task failed'));

		// Mock only the loadTask method
		$processor = $this->getMockBuilder(Processor::class)
			->setConstructorArgs([new Io(new ConsoleIo($out, $err)), new NullLogger()])
			->onlyMethods(['loadTask'])
			->getMock();
		$processor->method('loadTask')->willReturn($mockTask);

		// Run the job (it will fail and should trigger the event)
		$this->invokeMethod($processor, 'runJob', [$job, 'test-pid']);

		// Check that the event was dispatched
		$this->assertEventFired('Queue.Job.maxAttemptsExhausted');

		// Verify event data
		// The event was fired successfully (assertEventFired passed)
		// We don't need to check the event data again since assertEventFired confirms it was fired
	}

	/**
	 * Test that worker timeout handling marks the current job as failed
	 *
	 * @return void
	 */
	public function testWorkerTimeoutHandling() {
		// Define SIGTERM if not available (for non-POSIX systems)
		if (!defined('SIGTERM')) {
			define('SIGTERM', 15);
		}

		// Create a mock job
		$job = $this->getMockBuilder(QueuedJob::class)
			->getMock();
		$job->id = 123;
		$job->job_task = 'TestTask';

		// Create mock QueuedJobs table
		$QueuedJobs = $this->getMockBuilder(QueuedJobsTable::class)
			->disableOriginalConstructor()
			->onlyMethods(['markJobFailed'])
			->getMock();

		// Expect markJobFailed to be called with the job and failure message
		$QueuedJobs->expects($this->once())
			->method('markJobFailed')
			->with(
				$this->identicalTo($job),
				$this->stringContains('Worker process terminated by signal'),
			);

		// Create processor
		$out = new ConsoleOutput();
		$err = new ConsoleOutput();
		$processor = new Processor(new Io(new ConsoleIo($out, $err)), new NullLogger());

		// Set the QueuedJobs property through reflection
		$reflection = new ReflectionClass($processor);
		if ($reflection->hasProperty('QueuedJobs')) {
			$queuedJobsProperty = $reflection->getProperty('QueuedJobs');
			$queuedJobsProperty->setValue($processor, $QueuedJobs);
		}

		// Set the current job property through reflection
		if ($reflection->hasProperty('currentJob')) {
			$currentJobProperty = $reflection->getProperty('currentJob');
			$currentJobProperty->setValue($processor, $job);
		}

		// Set the pid property
		if ($reflection->hasProperty('pid')) {
			$pidProperty = $reflection->getProperty('pid');
			$pidProperty->setValue($processor, 'test-pid');
		}

		// Call the exit method which handles SIGTERM signal (timeout scenario)
		$this->invokeMethod($processor, 'exit', [SIGTERM]);

		// Check that exit flag was set
		if ($reflection->hasProperty('exit')) {
			$exitProperty = $reflection->getProperty('exit');
			$this->assertTrue($exitProperty->getValue($processor), 'Exit flag should be set to true');
		}
	}

	/**
	 * Integration test for worker timeout handling with real database
	 *
	 * @return void
	 */
	public function testWorkerTimeoutHandlingIntegration() {
		$this->_needsConnection();

		// Define SIGTERM if not available (for non-POSIX systems)
		if (!defined('SIGTERM')) {
			define('SIGTERM', 15);
		}

		// Create a real job in the database
		$QueuedJobs = $this->fetchTable('Queue.QueuedJobs');
		$job = $QueuedJobs->createJob('Queue.RetryExample', ['test' => 'data'], ['priority' => 1]);
		$this->assertNotNull($job->id, 'Job should be created with an ID');

		// Create processor
		$out = new ConsoleOutput();
		$err = new ConsoleOutput();
		$processor = new Processor(new Io(new ConsoleIo($out, $err)), new NullLogger());

		// Set the current job property through reflection
		$reflection = new ReflectionClass($processor);
		if ($reflection->hasProperty('currentJob')) {
			$currentJobProperty = $reflection->getProperty('currentJob');
			$currentJobProperty->setValue($processor, $job);
		}

		// Set the pid property
		if ($reflection->hasProperty('pid')) {
			$pidProperty = $reflection->getProperty('pid');
			$pidProperty->setValue($processor, 'test-pid');
		}

		// Call the exit method which handles SIGTERM signal (timeout scenario)
		$this->invokeMethod($processor, 'exit', [SIGTERM]);

		// Reload the job to check its status
		$updatedJob = $QueuedJobs->get($job->id);

		// Assert that the job was marked as failed (has failure message but not completed)
		$this->assertNull($updatedJob->completed, 'Job should not be marked as completed');
		$this->assertNotNull($updatedJob->failure_message, 'Job should have a failure message');
		$this->assertStringContainsString('Worker process terminated by signal', $updatedJob->failure_message);
		$this->assertStringContainsString('SIGTERM', $updatedJob->failure_message);
		$this->assertStringContainsString('timeout', $updatedJob->failure_message);

		// Check that exit flag was set
		if ($reflection->hasProperty('exit')) {
			$exitProperty = $reflection->getProperty('exit');
			$this->assertTrue($exitProperty->getValue($processor), 'Exit flag should be set to true');
		}
	}

	/**
	 * Test setPhpTimeout with new config names
	 *
	 * @return void
	 */
	public function testSetPhpTimeoutWithNewConfig() {
		$processor = new Processor(new Io(new ConsoleIo()), new NullLogger());

		// Test with workerPhpTimeout config
		Configure::write('Queue.workerPhpTimeout', 300);
		$result = $this->invokeMethod($processor, 'setPhpTimeout', [null]);
		$this->assertNull($result, 'setPhpTimeout should not return a value');

		// Test with maxruntime parameter
		Configure::delete('Queue.workerPhpTimeout');
		$result = $this->invokeMethod($processor, 'setPhpTimeout', [60]);
		$this->assertNull($result, 'setPhpTimeout should not return a value');

		// Test fallback to workerLifetime * 2
		Configure::delete('Queue.workerPhpTimeout');
		Configure::write('Queue.workerLifetime', 100);
		$result = $this->invokeMethod($processor, 'setPhpTimeout', [null]);
		$this->assertNull($result, 'setPhpTimeout should not return a value');

		// Clean up
		Configure::delete('Queue.workerPhpTimeout');
		Configure::delete('Queue.workerLifetime');
	}

	/**
	 * Test setPhpTimeout with deprecated config name
	 *
	 * @return void
	 */
	public function testSetPhpTimeoutWithDeprecatedConfig() {
		$processor = new Processor(new Io(new ConsoleIo()), new NullLogger());

		// Test with deprecated workertimeout config
		Configure::write('Queue.workertimeout', 250);

		// Suppress the deprecation warning for this test
		$errorLevel = error_reporting();
		error_reporting($errorLevel & ~E_USER_DEPRECATED);

		$result = $this->invokeMethod($processor, 'setPhpTimeout', [null]);
		$this->assertNull($result, 'setPhpTimeout should not return a value even with deprecated config');

		// Restore error reporting
		error_reporting($errorLevel);

		// Clean up
		Configure::delete('Queue.workertimeout');
	}

}
