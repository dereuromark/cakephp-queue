<?php
declare(strict_types=1);

namespace TestApp\Queue\Task;

use Queue\Queue\Task;
use TestApp\Services\TestService;

/**
 * Test task that uses constructor-based dependency injection.
 */
class InjectedTask extends Task {

	public ?int $timeout = 10;

	public function __construct(
		protected readonly TestService $testService,
	) {
		parent::__construct();
	}

	public function run(array $data, int $jobId): void {
		$this->io->out($this->testService->output());
	}

	/**
	 * Expose the injected service for test assertions.
	 */
	public function getTestService(): TestService {
		return $this->testService;
	}

}
