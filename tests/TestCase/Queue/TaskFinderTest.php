<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Queue;

use Cake\TestSuite\TestCase;
use Queue\Queue\Task\ExampleTask;
use Queue\Queue\TaskFinder;
use TestApp\Queue\Task\FooTask;

class TaskFinderTest extends TestCase {

	/**
	 * @var \Queue\Queue\TaskFinder
	 */
	protected $taskFinder;

	/**
	 * @return void
	 */
	public function testAllAppAndPluginTasks() {
		$this->taskFinder = new TaskFinder();

		$result = $this->taskFinder->all();

		$this->assertArrayHasKey('Queue.Example', $result);
		$this->assertArrayHasKey('Foo', $result);
		$this->assertArrayHasKey('Foo.Foo', $result);

		$this->assertSame('TestApp\Queue\Task\Sub\SubFooTask', $result['Sub/SubFoo']);
		$this->assertSame('Foo\Queue\Task\Sub\SubFooTask', $result['Foo.Sub/SubFoo']);
	}

	/**
	 * @return void
	 */
	public function testResolve(): void {
		$this->taskFinder = new TaskFinder();

		$result = $this->taskFinder->resolve('Foo');
		$this->assertSame('Foo', $result);

		$result = $this->taskFinder->resolve(FooTask::class);
		$this->assertSame('Foo', $result);

		$result = $this->taskFinder->resolve('Queue.Example');
		$this->assertSame('Queue.Example', $result);

		$result = $this->taskFinder->resolve(ExampleTask::class);
		$this->assertSame('Queue.Example', $result);

		$result = $this->taskFinder->resolve(ExampleTask::taskName());
		$this->assertSame('Queue.Example', $result);
	}

	/**
	 * @return void
	 */
	public function testClassName(): void {
		$this->taskFinder = new TaskFinder();

		$class = $this->taskFinder->getClass('Queue.Example');
		$this->assertSame(ExampleTask::class, $class);
	}

}
