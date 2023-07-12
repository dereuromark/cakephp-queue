<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\View\Helper;

use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Queue\Model\Entity\QueuedJob;
use Queue\View\Helper\QueueProgressHelper;
use Tools\Utility\Number;

class QueueProgressHelperTest extends TestCase {

	/**
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Queue.QueuedJobs',
	];

	/**
	 * @var \Queue\View\Helper\QueueProgressHelper
	 */
	protected QueueProgressHelper $QueueProgressHelper;

	/**
	 * @var string
	 */
	protected string $locale;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->locale = ini_get('intl.default_locale');
		ini_set('intl.default_locale', 'en-US');
		Number::config('en_EN');

		$this->QueueProgressHelper = new QueueProgressHelper(new View(null));
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		ini_set('intl.default_locale', $this->locale);
	}

	/**
	 * @return void
	 */
	public function testProgress() {
		$queuedJob = new QueuedJob([
			'progress' => 0.9999,
		]);
		$result = $this->QueueProgressHelper->progress($queuedJob);
		$this->assertSame('99%', $result);

		$queuedJob = new QueuedJob([
			'progress' => 0.0001,
		]);
		$result = $this->QueueProgressHelper->progress($queuedJob);
		$this->assertSame('1%', $result);

		$queuedJob = new QueuedJob([
			'progress' => 1.0,
		]);
		$result = $this->QueueProgressHelper->progress($queuedJob);
		$this->assertSame('100%', $result);

		$queuedJob = new QueuedJob([
			'progress' => 0.0,
		]);
		$result = $this->QueueProgressHelper->progress($queuedJob);
		$this->assertSame('0%', $result);
	}

	/**
	 * @return void
	 */
	public function testProgressCalculatedEmpty() {
		$queuedJob = new QueuedJob([
			'job_task' => 'Queue.Example',
			'fetched' => (new DateTime())->subMinutes(1),
		]);
		$result = $this->QueueProgressHelper->progress($queuedJob);
		$this->assertNull($result);
	}

	/**
	 * @return void
	 */
	public function testProgressCalculated() {
		$queuedJob = new QueuedJob([
			'job_task' => 'Queue.Example',
			'created' => (new DateTime())->subMinutes(2),
			'fetched' => (new DateTime())->subMinutes(1),
			'completed' => (new DateTime())->subSeconds(2),
		]);
		$this->getTableLocator()->get('Queue.QueuedJobs')->saveOrFail($queuedJob);

		$queuedJob = new QueuedJob([
			'job_task' => 'Queue.Example',
			'fetched' => (new DateTime())->subMinutes(1),
		]);

		$result = $this->QueueProgressHelper->progress($queuedJob);
		$this->assertSame('99%', $result);
	}

	/**
	 * @return void
	 */
	public function testProgressBar() {
		$queuedJob = new QueuedJob([
			'progress' => 0.47,
		]);
		$result = $this->QueueProgressHelper->progressBar($queuedJob, 5);
		$this->assertTextContains('<span title="47%">', $result);

		$queuedJob = new QueuedJob([
			'progress' => null,
		]);
		$result = $this->QueueProgressHelper->progressBar($queuedJob, 5);
		$this->assertNull($result);
	}

	/**
	 * @return void
	 */
	public function testHtmlProgressBar() {
		$queuedJob = new QueuedJob([
			'progress' => 0.47,
		]);
		$result = $this->QueueProgressHelper->htmlProgressBar($queuedJob);
		$expected = '<progress value="47" max="100" title="47%"></progress>';
		$this->assertSame($expected, $result);

		$queuedJob = new QueuedJob([
			'progress' => 0.9999,
		]);
		// For IE9 and below
		$fallback = $this->QueueProgressHelper->progressBar($queuedJob, 10);
		$result = $this->QueueProgressHelper->htmlProgressBar($queuedJob, $fallback);
		$expected = '<progress value="99" max="100" title="99%"><span title="99%">█████████░</span></progress>';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testProgressBarByStatistics() {
		$this->_needsConnection();

		/** @var \Queue\Model\Entity\QueuedJob $queuedJob */
		$queuedJob = $this->getTableLocator()->get('Queue.QueuedJobs')->newEntity([
			'job_task' => 'Foo',
			'created' => (new DateTime())->subHours(1),
			'fetched' => (new DateTime())->subHours(1),
			'completed' => (new DateTime())->subHours(1)->addMinutes(10),
		]);
		$this->getTableLocator()->get('Queue.QueuedJobs')->saveOrFail($queuedJob);

		$queuedJob->completed = null;
		$queuedJob->fetched = (new DateTime())->subMinutes(1);

		$result = $this->QueueProgressHelper->progressBar($queuedJob, 5);
		$this->assertTextContains('<span title="10%">', $result);
	}

	/**
	 * @return void
	 */
	public function testTimeoutProgressBar() {
		$queuedJob = new QueuedJob([
			'created' => (new DateTime())->subHours(1),
			'notbefore' => (new DateTime())->addHours(1),
		]);

		$result = $this->QueueProgressHelper->timeoutProgressBar($queuedJob, 5);
		$this->assertTextContains('<span title="50%">', $result);
	}

	/**
	 * @return void
	 */
	public function testHtmlTimeoutProgressBar() {
		$queuedJob = new QueuedJob([
			'created' => (new DateTime())->subMinutes(1),
			'notbefore' => (new DateTime())->addMinutes(1),
		]);
		$result = $this->QueueProgressHelper->htmlTimeoutProgressBar($queuedJob);
		$expected = '<progress value="50" max="100" title="50%"></progress>';
		$this->assertSame($expected, $result);

		$queuedJob = new QueuedJob([
			'created' => (new DateTime()),
			'notbefore' => (new DateTime())->addHours(1),
		]);
		// For IE9 and below
		$fallback = $this->QueueProgressHelper->timeoutProgressBar($queuedJob, 10);
		$result = $this->QueueProgressHelper->htmlTimeoutProgressBar($queuedJob, $fallback);
		$expected = '<progress value="0" max="100" title="0%"><span title="0%">░░░░░░░░░░</span></progress>';
		$this->assertSame($expected, $result);

		$queuedJob = new QueuedJob([
			'created' => (new DateTime())->subMinutes(1),
			'notbefore' => (new DateTime())->subSeconds(1),
		]);
		// For IE9 and below
		$fallback = $this->QueueProgressHelper->timeoutProgressBar($queuedJob, 10);
		$result = $this->QueueProgressHelper->htmlTimeoutProgressBar($queuedJob, $fallback);
		$expected = '<progress value="100" max="100" title="100%"><span title="100%">██████████</span></progress>';
		$this->assertSame($expected, $result);
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
