<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueuedJob[] $pendingDetails Capped at $detailsLimit rows.
 * @var \Queue\Model\Entity\QueuedJob[] $scheduledDetails Capped at $detailsLimit rows.
 * @var bool $pendingDetailsTruncated True when the pending list was capped.
 * @var bool $scheduledDetailsTruncated True when the scheduled list was capped.
 * @var int $detailsLimit Configured cap for the visible pending/scheduled rows.
 * @var int $totalPending True (uncapped) pending-jobs count.
 * @var string[] $tasks
 * @var string[] $addableTasks
 * @var array<string, string|null> $taskDescriptions
 * @var string[] $servers
 * @var array $status
 * @var int $new
 * @var int $current
 * @var array $data
 * @var int $workers
 * @var int $pendingJobs
 * @var int $scheduledJobs
 * @var int $runningJobs
 * @var int $failedJobs
 * @var array<string, mixed> $configurations
 */

use Cake\Core\Configure;

// Banner thresholds are a UI policy, not a system mechanic: how long the
// dashboard waits before nagging the admin. Defaults are 60s yellow / 120s
// red — human-perceptible minute boundaries — and don't derive from queue
// config knobs because none of them actually mean "heartbeat freshness":
//   - workerLifetime is an exit policy (a 1h-lifetime worker still
//     heartbeats every ~sleeptime when idle).
//   - defaultRequeueTimeout is the job-reassignment safeguard, tuned for
//     max job duration (often 5-10 min).
//   - sleeptime is closest to the real heartbeat cadence for an idle
//     worker, but busy workers don't sleep — and we already cover the
//     busy-worker case via the `runningJobs > 0` escape hatch below.
// Override these for installations with unusual cron cadence (e.g. slow
// `exitwhennothingtodo` cron — raise dashboardStalledAfter past the cron
// interval to avoid false-red between ticks).
$idleAfterSeconds = (int)Configure::read('Queue.dashboardIdleAfter', 60);
$stalledAfterSeconds = (int)Configure::read('Queue.dashboardStalledAfter', 120);
?>

<!-- Status Banner -->
<?php
/**
 * Three-state status (running / idle / stalled). State is computed for both the
 * "have a recent heartbeat" path and the "no active worker rows at all" path so
 * that a total cron outage — the worst case — surfaces as red, not as a muted
 * info notice.
 *
 *   running   <idleAfterSeconds                                       green
 *   idle      idleAfterSeconds-stalledAfterSeconds,
 *             OR no heartbeat & no backlog                            yellow
 *   stalled   ≥stalledAfterSeconds with pending backlog and no
 *             in-flight job, OR no heartbeat at all with pending      red
 *
 * Thresholds default to 60s yellow / 120s red — human-perceptible minute
 * boundaries — and can be tuned via Queue.dashboardIdleAfter and
 * Queue.dashboardStalledAfter for installs with unusual cron cadence.
 *
 * Notes:
 *   - In cron-driven mode workers are short-lived; `workers == 0` is the normal
 *     idle state for a quiet system, so red requires a real pending backlog too.
 *   - `runningJobs > 0` (derived in the controller from
 *     `fetched IS NOT NULL AND completed IS NULL`) keeps a busy worker out of
 *     red: heartbeats fire at the top of each loop, not during long jobs, so
 *     a >2 min task with more pending behind it would look stalled by heartbeat
 *     age alone.
 *   - When `$status` is empty, `QueueProcessesTable::status()` filtered every
 *     worker row past `Queue.defaultRequeueTimeout`. In that case a pending
 *     backlog or stuck in-flight job is unambiguously a problem.
 */
$state = 'idle';
$time = null;
$relTime = null;

if ($status) {
	/** @var \Cake\I18n\DateTime $time */
	$time = $status['time'];
	$now = new \Cake\I18n\DateTime();
	$secondsSinceActivity = max(0, $now->getTimestamp() - $time->getTimestamp());

	$state = 'running';
	if ($secondsSinceActivity >= $idleAfterSeconds) {
		$state = 'idle';
	}
	if ($secondsSinceActivity >= $stalledAfterSeconds && $pendingJobs > 0 && $runningJobs === 0) {
		$state = 'stalled';
	}

	$relTime = method_exists($this->Time, 'relLengthOfTime')
		? $this->Time->relLengthOfTime($status['time'])
		: $this->Time->timeAgoInWords($status['time']);
} elseif ($pendingJobs > 0 || $runningJobs > 0) {
	// No worker has reported within `Queue.defaultRequeueTimeout`, yet jobs are
	// either waiting (pending) or marked in-flight (fetched but not completed).
	// Pending + no heartbeat = cron likely dead. Running + no heartbeat = worker
	// died mid-job and left a stale fetched row, OR a job legitimately ran past
	// the requeue timeout (which is itself a misconfiguration worth surfacing).
	$state = 'stalled';
}

$stateMeta = [
	'running' => ['icon' => 'check-circle', 'iconColor' => 'text-success', 'label' => __d('queue', 'Queue Running')],
	'idle' => ['icon' => 'pause-circle', 'iconColor' => 'text-warning', 'label' => __d('queue', 'Queue Idle')],
	'stalled' => ['icon' => 'exclamation-circle', 'iconColor' => 'text-danger', 'label' => __d('queue', 'Queue Stalled')],
][$state];
?>
<div class="status-banner status-<?= $state ?>">
	<div class="d-flex align-items-center justify-content-between">
		<div class="d-flex align-items-center">
			<span class="status-icon me-3">
				<i class="fas fa-<?= $stateMeta['icon'] ?> <?= $stateMeta['iconColor'] ?>"></i>
			</span>
			<div>
				<strong><?= $stateMeta['label'] ?></strong>
				<?php if ($state === 'stalled'): ?>
					<span class="badge bg-danger ms-2"><?= __d('queue', 'action required') ?></span>
				<?php endif; ?>
				<div class="text-muted small">
					<?php if ($status): ?>
						<?= __d('queue', 'Last activity {0}', $relTime) ?>
						&bull;
					<?php else: ?>
						<?= __d('queue', 'No worker reporting') ?>
						&bull;
					<?php endif; ?>
					<?= $this->Html->link(
						__d('queue', '{0} worker(s)', $workers),
						['action' => 'processes'],
						['class' => 'text-decoration-none']
					) ?>
					&bull;
					<?= __d('queue', '{0} server(s)', count($servers)) ?>
				</div>
			</div>
		</div>
		<div>
			<?= $this->Html->link(
				'<i class="fas fa-cogs me-1"></i>' . __d('queue', 'Manage Workers'),
				['action' => 'processes'],
				['class' => 'btn btn-sm btn-outline-dark', 'escapeTitle' => false]
			) ?>
		</div>
	</div>
	<?php if ($state === 'stalled'): ?>
		<?php
		if (!$status && $runningJobs > 0) {
			$causeHint = __d('queue', 'A job is marked in-flight but no worker is reporting. The worker likely crashed mid-job — reset stale fetched jobs and check cron.');
		} elseif (!$status) {
			$causeHint = __d('queue', 'No worker has reported in. Cron is likely not firing — check that {0} runs on at least one server.', '<code>bin/cake queue run</code>');
		} elseif ($workers === 0) {
			$causeHint = __d('queue', 'Jobs are waiting but no workers are running. Check that {0} cron is firing on at least one server.', '<code>bin/cake queue run</code>');
		} else {
			$causeHint = __d('queue', "Jobs are waiting but aren't being picked up. Workers may have crashed — restart the queue or clean up stale processes.");
		}
		?>
		<div class="stalled-details mt-3 pt-3 border-top border-danger-subtle">
			<dl class="row mb-2 small">
				<dt class="col-sm-3 text-muted fw-normal"><?= __d('queue', 'Last activity') ?></dt>
				<dd class="col-sm-9 mb-1">
					<?php if ($time): ?>
						<code><?= h($time->i18nFormat('yyyy-MM-dd HH:mm:ss')) ?></code>
						<span class="text-muted">· <?= $relTime ?></span>
					<?php else: ?>
						<span class="text-danger fw-medium"><?= __d('queue', 'No worker has reported recently') ?></span>
					<?php endif; ?>
				</dd>
				<dt class="col-sm-3 text-muted fw-normal"><?= __d('queue', 'Workers') ?></dt>
				<dd class="col-sm-9 mb-1">
					<strong class="<?= $workers === 0 ? 'text-danger' : '' ?>"><?= $workers ?></strong>
					<?= __d('queue', 'on {0} server(s)', count($servers)) ?>
				</dd>
				<dt class="col-sm-3 text-muted fw-normal"><?= __d('queue', 'Pending') ?></dt>
				<dd class="col-sm-9 mb-0">
					<strong class="<?= $pendingJobs > 0 ? 'text-danger' : '' ?>"><?= $pendingJobs ?></strong>
					<?= __d('queue', 'jobs waiting') ?>
				</dd>
			</dl>
			<div class="small">
				<i class="fas fa-info-circle me-1 text-danger"></i>
				<?= $causeHint ?>
			</div>
		</div>
	<?php endif; ?>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
	<div class="col-md-3 col-sm-6">
		<?= $this->element('Queue.Queue/stats_card', [
			'title' => __d('queue', 'Scheduled'),
			'count' => $scheduledJobs,
			'icon' => 'calendar',
			'color' => 'info',
		]) ?>
	</div>
	<div class="col-md-3 col-sm-6">
		<?= $this->element('Queue.Queue/stats_card', [
			'title' => __d('queue', 'Pending'),
			'count' => $pendingJobs,
			'icon' => 'clock',
			'color' => 'warning',
		]) ?>
	</div>
	<div class="col-md-3 col-sm-6">
		<?= $this->element('Queue.Queue/stats_card', [
			'title' => __d('queue', 'Running'),
			'count' => $runningJobs,
			'icon' => 'spinner',
			'color' => 'primary',
		]) ?>
	</div>
	<div class="col-md-3 col-sm-6">
		<?= $this->element('Queue.Queue/stats_card', [
			'title' => __d('queue', 'Failed'),
			'count' => $failedJobs,
			'icon' => 'times-circle',
			'color' => 'danger',
		]) ?>
	</div>
</div>

<div class="row">
	<!-- Main Content Column -->
	<div class="col-lg-8">
		<!-- Pending Jobs Card -->
		<div class="card mb-4">
			<div class="card-header d-flex justify-content-between align-items-center">
				<span><i class="fas fa-tasks me-2"></i><?= __d('queue', 'Pending Jobs') ?> (<?= $new ?>/<?= $current ?>)</span>
				<?= $this->Html->link(
					__d('queue', 'View All'),
					['controller' => 'QueuedJobs', 'action' => 'index'],
					['class' => 'btn btn-sm btn-outline-primary']
				) ?>
			</div>
			<div class="card-body p-0">
				<?php if ($pendingDetailsTruncated): ?>
					<div class="alert alert-info mb-0 small rounded-0 border-0 border-bottom">
						<i class="fas fa-info-circle me-1"></i>
						<?= __d(
							'queue',
							'Showing {0} most recent of {1} pending jobs. {2} for the full list.',
							[
								count($pendingDetails),
								$totalPending,
								$this->Html->link(
									__d('queue', 'See QueuedJobs admin'),
									['controller' => 'QueuedJobs', 'action' => 'index', '?' => ['status' => 'in_progress']],
								),
							],
						) ?>
					</div>
				<?php endif; ?>
				<?php if ($pendingDetails): ?>
					<div class="table-responsive">
						<table class="table table-hover mb-0">
							<thead>
								<tr>
									<th><?= __d('queue', 'Task') ?></th>
									<th><?= __d('queue', 'Reference') ?></th>
									<th><?= __d('queue', 'Created') ?></th>
									<th><?= __d('queue', 'Status') ?></th>
									<th><?= __d('queue', 'Actions') ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($pendingDetails as $pendingJob): ?>
									<tr>
										<td>
											<?= $this->Html->link(
												h($pendingJob->job_task),
												['controller' => 'QueuedJobs', 'action' => 'view', $pendingJob->id],
												['class' => 'text-decoration-none fw-medium']
											) ?>
										</td>
										<td>
											<code class="small"><?= h($pendingJob->reference ?: '-') ?></code>
										</td>
										<td class="text-muted small">
											<?= $this->Time->nice($pendingJob->created) ?>
											<?php if ($pendingJob->notbefore): ?>
												<br><span class="badge bg-info text-dark"><?= __d('queue', 'Scheduled {0}', $this->Time->nice($pendingJob->notbefore)) ?></span>
											<?php endif; ?>
										</td>
										<td>
											<?php if ($this->Queue->hasFailed($pendingJob)): ?>
												<span class="badge badge-failed">
													<i class="fas fa-times me-1"></i><?= __d('queue', 'Failed') ?>
												</span>
												<div class="small text-muted"><?= __d('queue', 'Attempts') ?>: <?= $this->Queue->attempts($pendingJob) ?></div>
											<?php elseif ($this->Queue->isRequeued($pendingJob)): ?>
												<span class="badge bg-warning text-dark">
													<i class="fas fa-redo me-1"></i><?= __d('queue', 'Requeued') ?>
												</span>
												<div class="small text-muted"><?= __d('queue', 'Attempts') ?>: <?= $this->Queue->attempts($pendingJob) ?></div>
											<?php elseif ($this->Queue->isRestarted($pendingJob)): ?>
												<span class="badge bg-info text-dark">
													<i class="fas fa-sync me-1"></i><?= __d('queue', 'Restarted') ?>
												</span>
												<div class="small text-muted"><?= __d('queue', 'Attempts') ?>: <?= $this->Queue->attempts($pendingJob) ?></div>
											<?php elseif ($pendingJob->fetched): ?>
												<span class="badge badge-running">
													<i class="fas fa-spinner fa-spin me-1"></i><?= __d('queue', 'Running') ?>
												</span>
												<?php if (!$pendingJob->failure_message): ?>
													<div class="mt-1">
														<?= $this->QueueProgress->htmlProgressBar($pendingJob, $this->QueueProgress->progressBar($pendingJob, 18)) ?>
													</div>
												<?php endif; ?>
											<?php else: ?>
												<span class="badge badge-pending">
													<i class="fas fa-clock me-1"></i><?= __d('queue', 'Pending') ?>
												</span>
											<?php endif; ?>
										</td>
										<td>
											<?php if ($this->Queue->hasFailed($pendingJob)): ?>
												<?= $this->Form->postButton(
													'<i class="fas fa-redo"></i>',
													['action' => 'resetJob', $pendingJob->id],
													[
														'escapeTitle' => false,
														'class' => 'btn btn-sm btn-outline-primary',
														'title' => __d('queue', 'Reset'),
														'form' => [
															'class' => 'd-inline',
															'data-confirm-message' => __d('queue', 'Sure?'),
														],
													]
												) ?>
												<?= $this->Form->postButton(
													'<i class="fas fa-trash"></i>',
													['action' => 'removeJob', $pendingJob->id],
													[
														'escapeTitle' => false,
														'class' => 'btn btn-sm btn-outline-danger',
														'title' => __d('queue', 'Remove'),
														'form' => [
															'class' => 'd-inline',
															'data-confirm-message' => __d('queue', 'Sure?'),
														],
													]
												) ?>
											<?php elseif ($pendingJob->fetched): ?>
												<?= $this->Form->postButton(
													'<i class="fas fa-trash"></i>',
													['action' => 'removeJob', $pendingJob->id],
													[
														'escapeTitle' => false,
														'class' => 'btn btn-sm btn-outline-danger',
														'title' => __d('queue', 'Remove'),
														'form' => [
															'class' => 'd-inline',
															'data-confirm-message' => __d('queue', 'Sure?'),
														],
													]
												) ?>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else: ?>
					<div class="text-center text-muted py-4">
						<i class="fas fa-inbox fa-2x mb-2"></i>
						<p class="mb-0"><?= __d('queue', 'No pending jobs') ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Scheduled Jobs Card -->
		<?php if ($scheduledDetails): ?>
			<div class="card mb-4">
				<div class="card-header">
					<i class="fas fa-calendar me-2"></i><?= __d('queue', 'Scheduled Jobs') ?> (<?= $scheduledJobs ?>)
				</div>
				<div class="card-body p-0">
					<?php if ($scheduledDetailsTruncated): ?>
						<div class="alert alert-info mb-0 small rounded-0 border-0 border-bottom">
							<i class="fas fa-info-circle me-1"></i>
							<?= __d(
								'queue',
								'Showing {0} most recent of {1} scheduled jobs. {2} for the full list.',
								[
									count($scheduledDetails),
									$scheduledJobs,
									$this->Html->link(
										__d('queue', 'See QueuedJobs admin'),
										['controller' => 'QueuedJobs', 'action' => 'index', '?' => ['status' => 'scheduled']],
									),
								],
							) ?>
						</div>
					<?php endif; ?>
					<div class="table-responsive">
						<table class="table table-hover mb-0">
							<thead>
								<tr>
									<th><?= __d('queue', 'Task') ?></th>
									<th><?= __d('queue', 'Reference') ?></th>
									<th><?= __d('queue', 'Scheduled For') ?></th>
									<th><?= __d('queue', 'Actions') ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($scheduledDetails as $scheduledJob): ?>
									<tr>
										<td>
											<?= $this->Html->link(
												h($scheduledJob->job_task),
												['controller' => 'QueuedJobs', 'action' => 'view', $scheduledJob->id]
											) ?>
										</td>
										<td><code class="small"><?= h($scheduledJob->reference ?: '-') ?></code></td>
										<td>
											<?= $this->Time->nice($scheduledJob->notbefore) ?>
											<?php if ($scheduledJob->notbefore): ?>
												<div class="small text-muted">
													<?= method_exists($this->Time, 'relLengthOfTime')
														? $this->Time->relLengthOfTime($scheduledJob->notbefore)
														: $this->Time->timeAgoInWords($scheduledJob->notbefore) ?>
												</div>
											<?php endif; ?>
										</td>
										<td>
											<?= $this->Form->postButton(
												'<i class="fas fa-trash"></i>',
												['action' => 'removeJob', $scheduledJob->id],
												[
													'escapeTitle' => false,
													'class' => 'btn btn-sm btn-outline-danger',
													'title' => __d('queue', 'Remove'),
													'form' => [
														'class' => 'd-inline',
														'data-confirm-message' => __d('queue', 'Sure?'),
													],
												]
											) ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<!-- Statistics Card -->
		<?php if ($data): ?>
			<div class="card mb-4">
				<div class="card-header">
					<i class="fas fa-chart-bar me-2"></i><?= __d('queue', 'Statistics') ?>
				</div>
				<div class="card-body p-0">
					<div class="table-responsive">
						<table class="table table-hover mb-0">
							<thead>
								<tr>
									<th><?= __d('queue', 'Task') ?></th>
									<th><?= __d('queue', 'Finished') ?></th>
									<th><?= __d('queue', 'Avg. Existence') ?></th>
									<th><?= __d('queue', 'Avg. Delay') ?></th>
									<th><?= __d('queue', 'Avg. Runtime') ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($data as $row): ?>
									<tr>
										<td><?= h($row['job_task']) ?></td>
										<td><?= $row['num'] ?></td>
										<td><?= $row['alltime'] ?>s</td>
										<td><?= $row['fetchdelay'] ?>s</td>
										<td><?= $row['runtime'] ?>s</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php if (Configure::read('Queue.isStatisticEnabled')): ?>
					<div class="card-footer d-flex gap-3">
						<?= $this->Html->link(
							'<i class="fas fa-chart-line me-1"></i>' . __d('queue', 'Time Series'),
							['controller' => 'QueuedJobs', 'action' => 'stats'],
							['class' => 'text-decoration-none', 'escapeTitle' => false]
						) ?>
						<?= $this->Html->link(
							'<i class="fas fa-th me-1"></i>' . __d('queue', 'Heatmap'),
							['controller' => 'QueuedJobs', 'action' => 'heatmap'],
							['class' => 'text-decoration-none', 'escapeTitle' => false]
						) ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<!-- Sidebar Column -->
	<div class="col-lg-4">
		<!-- Trigger Jobs Card -->
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-play me-2"></i><?= __d('queue', 'Trigger Jobs') ?>
			</div>
			<div class="card-body">
				<p class="small text-muted"><?= __d('queue', 'Jobs implementing AddFromBackendInterface') ?></p>
				<?php if ($addableTasks): ?>
					<div class="d-grid gap-2">
						<?php foreach ($addableTasks as $task => $className): ?>
							<?php
							if (str_starts_with($task, 'Queue.') && (str_ends_with($task, 'Example') || $task === 'Queue.Execute')) {
								continue;
							}
							?>
							<?php $description = $taskDescriptions[$task] ?? null; ?>
							<?= $this->Form->postButton(
								'<i class="fas fa-plus me-1"></i>' . h($task),
								['action' => 'addJob', '?' => ['task' => $task]],
								[
									'escapeTitle' => false,
									'class' => 'btn btn-outline-primary btn-sm text-start w-100',
									'title' => $description,
									'form' => [
										'class' => 'd-inline',
										'data-confirm-message' => __d('queue', 'Sure?'),
									],
								]
							) ?>
						<?php endforeach; ?>
					</div>
				<?php else: ?>
					<p class="text-muted mb-0"><?= __d('queue', 'No addable tasks available') ?></p>
				<?php endif; ?>
				<?php if (Configure::read('debug')): ?>
				<hr>
				<?= $this->Html->link(
					'<i class="fas fa-terminal me-1"></i>' . __d('queue', 'Execute Job'),
					['controller' => 'QueuedJobs', 'action' => 'execute'],
					['class' => 'btn btn-outline-warning btn-sm w-100', 'escapeTitle' => false]
				) ?>
			<?php endif; ?>

				<hr>
				<a class="small text-muted text-decoration-none d-flex align-items-center" data-bs-toggle="collapse" href="#demoJobsCollapse" role="button" aria-expanded="false" aria-controls="demoJobsCollapse">
					<i class="fas fa-chevron-right me-1 collapse-icon"></i>
					<?= __d('queue', 'Test/Demo Jobs') ?>
				</a>
				<div class="collapse mt-2" id="demoJobsCollapse">
					<div class="d-grid gap-2">
						<?php foreach ($tasks as $task => $className): ?>
							<?php if (!str_ends_with($task, 'Example')) {
								continue;
							} ?>
							<?php $description = $taskDescriptions[$task] ?? null; ?>
							<?= $this->Form->postButton(
								'<i class="fas fa-flask me-1"></i>' . h($task),
								['action' => 'addJob', '?' => ['task' => $task]],
								[
									'escapeTitle' => false,
									'class' => 'btn btn-outline-secondary btn-sm text-start w-100',
									'title' => $description,
									'form' => [
										'class' => 'd-inline',
										'data-confirm-message' => __d('queue', 'Sure?'),
									],
								]
							) ?>
						<?php endforeach; ?>
						<?= $this->Html->link(
							'<i class="fas fa-clock me-1"></i>' . __d('queue', 'Trigger Delayed Test Job'),
							['controller' => 'QueuedJobs', 'action' => 'test'],
							['class' => 'btn btn-outline-info btn-sm', 'escapeTitle' => false]
						) ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Configuration Card -->
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-cog me-2"></i><?= __d('queue', 'Configuration') ?>
			</div>
			<div class="card-body">
				<div class="mb-3">
					<strong><?= __d('queue', 'Server') ?></strong>
					<ul class="list-unstyled mb-0 mt-1">
						<li class="d-flex justify-content-between">
							<span><code>posix</code> <?= __d('queue', 'extension') ?></span>
							<?= $this->element('Queue.yes_no', ['value' => function_exists('posix_kill')]) ?>
						</li>
					</ul>
				</div>
				<div>
					<strong><?= __d('queue', 'Runtime Configuration') ?></strong>
					<?php if ($configurations): ?>
						<?php
						$timeConfigKeys = [
							'workerLifetime',
							'workerPhpTimeout',
							'defaultRequeueTimeout',
							'cleanuptimeout',
							'sleeptime',
							'workermaxruntime',
							'workertimeout',
							'defaultworkertimeout',
						];
						?>
						<ul class="list-unstyled mt-1 small">
							<?php foreach ($configurations as $key => $configuration): ?>
								<li class="d-flex justify-content-between py-1 border-bottom">
									<span class="text-muted"><?= h($key) ?></span>
									<span>
										<?php
										if (is_string($configuration) && is_dir($configuration)) {
											$configuration = str_replace(ROOT . DS, 'ROOT' . DS, $configuration);
											$configuration = str_replace(DS, '/', $configuration);
										} elseif (is_bool($configuration)) {
											$configuration = $configuration ? 'true' : 'false';
										} elseif (is_array($configuration)) {
											$configuration = implode(', ', $configuration);
										} elseif (is_int($configuration) && in_array($key, $timeConfigKeys, true)) {
											$configuration = $this->Queue->secondsToHumanReadable($configuration);
										}
										echo '<code>' . h($configuration) . '</code>';
										?>
									</span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else: ?>
						<p class="text-muted mb-0"><?= __d('queue', 'No configuration found') ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>
