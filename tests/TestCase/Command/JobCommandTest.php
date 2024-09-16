<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Queue\Model\Entity\QueuedJob;

/**
 * @uses \Queue\Command\JobCommand
 */
class JobCommandTest extends TestCase {

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

		$this->loadPlugins(['Queue']);

		Configure::write('Queue.cleanuptimeout', 10);
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		Configure::delete('Queue.cleanuptimeout');
	}

	/**
	 * @return void
	 */
	public function testExecute(): void {
		$this->exec('queue job');

		$output = $this->_out->output();
		$this->assertStringContainsString('Please use with [action] [ID] added', $output);
		$this->assertExitCode(1);
	}

	/**
	 * @return void
	 */
	public function testExecuteView(): void {
		$job = $this->createJob();
		$this->exec('queue job view ' . $job->id);

		$output = $this->_out->output();
		$this->assertStringContainsString('Task: Example', $output);
	}

	/**
	 * @return void
	 */
	public function testExecuteRemove(): void {
		$job = $this->createJob();
		$this->exec('queue job remove ' . $job->id);

		$output = $this->_out->output();
		$this->assertStringContainsString('removed', $output);
	}

	/**
	 * @return void
	 */
	public function testExecuteRemoveAll(): void {
		$job = $this->createJob();
		$this->exec('queue job remove all');

		$output = $this->_out->output();
		$this->assertStringContainsString('removed', $output);
	}

	/**
	 * @return void
	 */
	public function testExecuteRerun(): void {
		$job = $this->createJob(['completed' => new DateTime()]);
		$this->exec('queue job rerun ' . $job->id);

		$output = $this->_out->output();
		$this->assertStringContainsString('queued for rerun', $output);
	}

	/**
	 * @return void
	 */
	public function testExecuteRerunAll(): void {
		$this->createJob(['completed' => new DateTime()]);
		$this->exec('queue job rerun all');

		$output = $this->_out->output();
		$this->assertStringContainsString('queued for rerun', $output);
		$this->assertExitCode(0);
	}

	/**
	 * @return void
	 */
	public function testExecuteClean(): void {
		$this->exec('queue job clean');

		$output = $this->_out->output();
		$this->assertStringContainsString('Deleted: ', $output);
	}

	/**
	 * @return void
	 */
	public function testExecuteFlush(): void {
		$this->exec('queue job flush');

		$output = $this->_out->output();
		$this->assertStringContainsString('Deleted: ', $output);
	}

	/**
	 * @param array $data
	 *
	 * @return \Queue\Model\Entity\QueuedJob
	 */
	protected function createJob(array $data = []): QueuedJob {
		$data += [
			'job_task' => 'Example',
		];
		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $this->getTableLocator()->get('Queue.QueuedJobs')->newEntity($data);
		$this->getTableLocator()->get('Queue.QueuedJobs')->saveOrFail($job);

		return $job;
	}

}
