<?php
declare(strict_types=1);

namespace TestApp\Queue\Task;

use Psr\Log\LoggerInterface;
use Queue\Console\Io;
use Queue\Queue\Task;
use TestApp\Services\TestService;

/**
 * Test task that uses constructor-based dependency injection.
 */
class InjectedTask extends Task {

	public ?int $timeout = 10;

	protected readonly TestService $testService;

	public function __construct(
		?Io $io = null,
		?LoggerInterface $logger = null,
		?TestService $testService = null,
	) {
		parent::__construct($io, $logger);
		$this->testService = $testService ?? new TestService();
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
