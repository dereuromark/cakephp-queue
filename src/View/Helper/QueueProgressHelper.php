<?php
declare(strict_types=1);

namespace Queue\View\Helper;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\I18n\Number;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\View\Helper;
use Queue\Model\Entity\QueuedJob;

/**
 * @property \Tools\View\Helper\ProgressHelper $Progress
 * @method \Cake\ORM\Locator\TableLocator getTableLocator()
 */
class QueueProgressHelper extends Helper {

	use LocatorAwareTrait;

	/**
	 * @var array<mixed>
	 */
	protected array $helpers = [
		'Tools.Progress',
	];

	/**
	 * @var array<string, array<int>>|null
	 */
	protected ?array $statistics = null;

	/**
	 * Returns percentage as formatted value.
	 *
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 *
	 * @return string|null
	 */
	public function progress(QueuedJob $queuedJob): ?string {
		if ($queuedJob->completed) {
			return null;
		}

		if ($queuedJob->progress === null && $queuedJob->fetched) {
			$queuedJob->progress = $this->calculateJobProgress($queuedJob->job_task, $queuedJob->fetched);
		}

		if ($queuedJob->progress === null) {
			return null;
		}

		$progress = $this->Progress->roundPercentage($queuedJob->progress);

		return Number::toPercentage($progress, 0, ['multiply' => true]);
	}

	/**
	 * Returns percentage as visual progress bar.
	 *
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 * @param int $length
	 *
	 * @return string|null
	 */
	public function progressBar(QueuedJob $queuedJob, int $length): ?string {
		if ($queuedJob->completed) {
			return null;
		}

		if ($queuedJob->progress === null && $queuedJob->fetched) {
			$queuedJob->progress = $this->calculateJobProgress($queuedJob->job_task, $queuedJob->fetched);
		}

		if ($queuedJob->progress === null) {
			return null;
		}

		return $this->Progress->progressBar($queuedJob->progress, $length);
	}

	/**
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 * @param string|null $fallbackHtml
	 *
	 * @return string|null
	 */
	public function htmlProgressBar(QueuedJob $queuedJob, ?string $fallbackHtml = null): ?string {
		if ($queuedJob->completed) {
			return null;
		}

		if ($queuedJob->progress === null && $queuedJob->fetched) {
			$queuedJob->progress = $this->calculateJobProgress($queuedJob->job_task, $queuedJob->fetched);
		}

		if ($queuedJob->progress === null) {
			return null;
		}

		$progress = $this->Progress->roundPercentage($queuedJob->progress);
		$title = Number::toPercentage($progress, 0, ['multiply' => true]);

		return '<progress value="' . number_format($progress * 100, 0) . '" max="100" title="' . $title . '">' . $fallbackHtml . '</progress>';
	}

	/**
	 * Returns percentage as visual progress bar.
	 *
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 * @param int $length
	 *
	 * @return string|null
	 */
	public function timeoutProgressBar(QueuedJob $queuedJob, int $length): ?string {
		$progress = $this->calculateTimeoutProgress($queuedJob);
		if ($progress === null) {
			return null;
		}

		return $this->Progress->progressBar($progress, $length);
	}

	/**
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 * @param string|null $fallbackHtml
	 *
	 * @return string|null
	 */
	public function htmlTimeoutProgressBar(QueuedJob $queuedJob, ?string $fallbackHtml = null): ?string {
		$progress = $this->calculateTimeoutProgress($queuedJob);
		if ($progress === null) {
			return null;
		}

		$progress = $this->Progress->roundPercentage($progress);
		$title = Number::toPercentage($progress, 0, ['multiply' => true]);

		return '<progress value="' . number_format($progress * 100, 0) . '" max="100" title="' . $title . '">' . $fallbackHtml . '</progress>';
	}

	/**
	 * Calculates the timeout progress rate.
	 *
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 *
	 * @return float|null
	 */
	protected function calculateTimeoutProgress(QueuedJob $queuedJob): ?float {
		if ($queuedJob->completed || $queuedJob->fetched || !$queuedJob->notbefore) {
			return null;
		}

		$created = $queuedJob->created->getTimestamp();
		$planned = $queuedJob->notbefore->getTimestamp();
		$now = (new DateTime())->getTimestamp();

		$progressed = $now - $created;
		$total = $planned - $created;

		if ($total <= 0) {
			return null;
		}

		if ($progressed < 0) {
			$progressed = 0;
		}

		$progress = min($progressed / $total, 1.0);

		return (float)$progress;
	}

	/**
	 * @param string $jobType
	 * @param \Cake\I18n\DateTime $fetched
	 *
	 * @return float|null
	 */
	protected function calculateJobProgress(string $jobType, DateTime $fetched): ?float {
		$stats = $this->getJobStatistics($jobType);
		if (!$stats) {
			return null;
		}
		$sum = array_sum($stats);
		if ($sum <= 0) {
			return null;
		}
		$average = $sum / count($stats);

		$running = $fetched->diffInSeconds();
		$progress = min($running / $average, 0.9999);

		return (float)$progress;
	}

	/**
	 * @param string $jobType
	 *
	 * @return array<int>
	 */
	protected function getJobStatistics(string $jobType): array {
		$statistics = $this->readStatistics();
		if (!isset($statistics[$jobType])) {
			return [];
		}

		return $statistics[$jobType];
	}

	/**
	 * @var string
	 */
	public const KEY = 'queue_queued-job-statistics';

	/**
	 * @var string
	 */
	public const CONFIG = 'default';

	/**
	 * @return array<string, array<int>>
	 */
	protected function readStatistics(): array {
		if ($this->statistics !== null) {
			return $this->statistics;
		}

		$queuedJobStatistics = false;
		if (!Configure::read('debug')) {
			$queuedJobStatistics = Cache::read(static::KEY, static::CONFIG);
		}
		if ($queuedJobStatistics === false) {
			$QueuedJobs = $this->getTableLocator()->get('Queue.QueuedJobs');
			$queuedJobStatistics = $QueuedJobs->getStats(true);
			Cache::write(static::KEY, $queuedJobStatistics, static::CONFIG);
		}

		$statistics = [];
		foreach ((array)$queuedJobStatistics as $statistic) {
			/** @var string $name */
			$name = $statistic['job_task'];
			$statistics[$name][] = $statistic['runtime'];
		}

		$this->statistics = $statistics;

		return $this->statistics;
	}

}
