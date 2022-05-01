<?php

namespace TestApp\Queue\Task;

use Cake\Core\ContainerInterface;
use Queue\Queue\AddInterface;
use Queue\Queue\Task;
use TestApp\Services\TestService;

class FooTask extends Task implements AddInterface {

	/**
	 * Timeout for run, after which the Task is reassigned to a new worker.
	 *
	 * @var int
	 */
	public $timeout = 10;

	/**
	 * Number of times a failed instance of this task should be restarted before giving up.
	 *
	 * @var int
	 */
	public $retries = 1;

	/**
	 * @var \TestApp\Services\TestService
	 */
	protected $testService;

	/**
	 * Example run function.
	 * This function is executed, when a worker is executing a task.
	 * The return parameter will determine, if the task will be marked completed, or be requeued.
	 *
	 * @param array<string, mixed> $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		$this->io->out('CakePHP Foo Example.');
		$test = $this->testService->output();
		$this->io->out($test);
	}

	/**
	 * @inheritDoc
	 */
	public function services(ContainerInterface $container): void {
		$this->testService = $container->get(TestService::class);
	}

	/**
	 * @inheritDoc
	 */
	public function add(?string $data): void {
		$this->QueuedJobs->createJob('Foo', $data);
	}

}
