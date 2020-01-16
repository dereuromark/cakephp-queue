<?php

namespace Queue\View\Helper;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Datasource\ModelAwareTrait;
use Cake\I18n\FrozenTime;
use Cake\I18n\Number;
use Cake\View\Helper;
use Queue\Model\Entity\QueuedJob;

/**
 * @property \Tools\View\Helper\ProgressHelper $Progress
 * @property \Queue\Model\Table\QueuedJobsTable $QueuedJobs
 */
class QueueProgressHelper extends Helper {

	use ModelAwareTrait;

	/**
	 * @var array
	 */
	public $helpers = [
		'Tools.Progress',
	];

	/**
	 * @var array|null
	 */
	protected $statistics;

	/**
	 * Returns percentage as formatted value.
	 *
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 * @return string|null
	 */
	public function progress(QueuedJob $queuedJob) {
		if ($queuedJob->completed) {
			return null;
		}

		if ($queuedJob->progress === null && $queuedJob->fetched) {
			$queuedJob->progress = $this->calculateJobProgress($queuedJob->job_type, $queuedJob->fetched);
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
	 * @return string|null
	 */
	public function progressBar(QueuedJob $queuedJob, $length) {
		if ($queuedJob->completed) {
			return null;
		}

		if ($queuedJob->progress === null && $queuedJob->fetched) {
			$queuedJob->progress = $this->calculateJobProgress($queuedJob->job_type, $queuedJob->fetched);
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
	public function htmlProgressBar(QueuedJob $queuedJob, $fallbackHtml = null) {
		if ($queuedJob->completed) {
			return null;
		}

		if ($queuedJob->progress === null && $queuedJob->fetched) {
			$queuedJob->progress = $this->calculateJobProgress($queuedJob->job_type, $queuedJob->fetched);
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
	 * @return string|null
	 */
	public function timeoutProgressBar(QueuedJob $queuedJob, $length) {
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
	public function htmlTimeoutProgressBar(QueuedJob $queuedJob, $fallbackHtml = null) {
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
	 * @return float|null
	 */
	protected function calculateTimeoutProgress(QueuedJob $queuedJob) {
		if ($queuedJob->completed || $queuedJob->fetched || !$queuedJob->notbefore) {
			return null;
		}

		$created = $queuedJob->created->getTimestamp();
		$planned = $queuedJob->notbefore->getTimestamp();
		$now = (new FrozenTime())->getTimestamp();

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
	 * @param \Cake\I18n\FrozenTime|\Cake\I18n\Time $fetched
	 * @return float|null
	 */
	protected function calculateJobProgress($jobType, $fetched) {
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
	 * @return array
	 */
	protected function getJobStatistics($jobType) {
		$statistics = $this->readStatistics();
		if (!isset($statistics[$jobType])) {
			return [];
		}

		return $statistics[$jobType];
	}

	const KEY = 'queue_queued-job-statistics';
	const CONFIG = 'default';

	/**
	 * @return array
	 */
	protected function readStatistics() {
		if ($this->statistics !== null) {
			return $this->statistics;
		}

		$queuedJobStatistics = false;
		if (!Configure::read('debug')) {
			$queuedJobStatistics = Cache::read(static::KEY, static::CONFIG);
		}
		if ($queuedJobStatistics === false) {
			$this->loadModel('Queue.QueuedJobs');
			$queuedJobStatistics = $this->QueuedJobs->getStats()->disableHydration()->toArray();
			Cache::write(static::KEY, $queuedJobStatistics, static::CONFIG);
		}

		$statistics = [];
		foreach ($queuedJobStatistics as $statistic) {
			$statistics[$statistic['job_type']][] = $statistic['runtime'];
		}

		$this->statistics = $statistics;

		return $this->statistics;
	}

}
