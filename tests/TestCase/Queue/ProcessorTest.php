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
use Queue\Queue\Processor;
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
			'defaultworkertimeout' => 3,
			'workermaxruntime' => 3,
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
		$this->_needsConnection();

		// Set up event tracking
		$eventList = new EventList();
		EventManager::instance()->setEventList($eventList);

		$out = new ConsoleOutput();
		$err = new ConsoleOutput();
		$this->Processor = new Processor(new Io(new ConsoleIo($out, $err)), new NullLogger());

		// Create a job that will fail
		$QueuedJobs = $this->getTableLocator()->get('Queue.QueuedJobs');
		$job = $QueuedJobs->createJob('RetryExample', [], ['priority' => 1]);

		// Set up the job to have already failed max attempts
		$taskConf = [
			'QueueRetryExample' => [
				'class' => 'Queue\Queue\Task\RetryExampleTask',
				'retries' => 2,
			],
		];
		$job->attempts = 3; // More than retries
		$QueuedJobs->save($job);

		// Mock the processor to use our task config
		$processor = $this->getMockBuilder(Processor::class)
			->setConstructorArgs([new Io(new ConsoleIo($out, $err)), new NullLogger()])
			->onlyMethods(['getTaskConf'])
			->getMock();
		$processor->method('getTaskConf')->willReturn($taskConf);

		// Run the job (it will fail)
		$this->invokeMethod($processor, 'runJob', [$job, 'test-pid']);

		// Check that the event was dispatched
		$this->assertEventFired('Queue.Job.maxAttemptsExhausted');

		// Verify event data
		$event = collection($eventList)->firstMatch(['name' => 'Queue.Job.maxAttemptsExhausted']);
		$this->assertNotNull($event);
		$this->assertArrayHasKey('job', $event->getData());
		$this->assertArrayHasKey('failureMessage', $event->getData());
		$this->assertSame($job->id, $event->getData()['job']->id);
	}

}
