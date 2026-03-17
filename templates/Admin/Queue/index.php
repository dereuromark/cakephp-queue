<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueuedJob[] $pendingDetails
 * @var \Queue\Model\Entity\QueuedJob[] $scheduledDetails
 * @var string[] $tasks
 * @var string[] $addableTasks
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

?>

<!-- Status Banner -->
<?php if ($status): ?>
	<?php
	/** @var \Cake\I18n\DateTime $time */
	$time = $status['time'];
	$running = $time->addMinutes(1)->isFuture();
	$relTime = method_exists($this->Time, 'relLengthOfTime')
		? $this->Time->relLengthOfTime($status['time'])
		: $this->Time->timeAgoInWords($status['time']);
	?>
	<div class="status-banner <?= $running ? 'status-running' : 'status-idle' ?>">
		<div class="d-flex align-items-center justify-content-between">
			<div class="d-flex align-items-center">
				<span class="status-icon me-3">
					<?php if ($running): ?>
						<i class="fas fa-check-circle text-success"></i>
					<?php else: ?>
						<i class="fas fa-pause-circle text-warning"></i>
					<?php endif; ?>
				</span>
				<div>
					<strong><?= $running ? __d('queue', 'Queue Running') : __d('queue', 'Queue Idle') ?></strong>
					<div class="text-muted small">
						<?= __d('queue', 'Last activity {0}', $relTime) ?>
						&bull;
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
	</div>
<?php else: ?>
	<div class="alert alert-secondary">
		<i class="fas fa-info-circle me-2"></i>
		<?= __d('queue', 'No queue status available. Workers may not have started yet.') ?>
	</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
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
			'title' => __d('queue', 'Scheduled'),
			'count' => $scheduledJobs,
			'icon' => 'calendar',
			'color' => 'info',
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
												<?= $this->Form->postLink(
													'<i class="fas fa-redo"></i>',
													['action' => 'resetJob', $pendingJob->id],
													[
														'escapeTitle' => false,
														'class' => 'btn btn-sm btn-outline-primary',
														'confirm' => __d('queue', 'Sure?'),
														'title' => __d('queue', 'Reset'),
														'block' => true,
													]
												) ?>
												<?= $this->Form->postLink(
													'<i class="fas fa-trash"></i>',
													['action' => 'removeJob', $pendingJob->id],
													[
														'escapeTitle' => false,
														'class' => 'btn btn-sm btn-outline-danger',
														'confirm' => __d('queue', 'Sure?'),
														'title' => __d('queue', 'Remove'),
														'block' => true,
													]
												) ?>
											<?php elseif ($pendingJob->fetched): ?>
												<?= $this->Form->postLink(
													'<i class="fas fa-trash"></i>',
													['action' => 'removeJob', $pendingJob->id],
													[
														'escapeTitle' => false,
														'class' => 'btn btn-sm btn-outline-danger',
														'confirm' => __d('queue', 'Sure?'),
														'title' => __d('queue', 'Remove'),
														'block' => true,
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
					<i class="fas fa-calendar me-2"></i><?= __d('queue', 'Scheduled Jobs') ?> (<?= count($scheduledDetails) ?>)
				</div>
				<div class="card-body p-0">
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
											<?= $this->Form->postLink(
												'<i class="fas fa-trash"></i>',
												['action' => 'removeJob', $scheduledJob->id],
												[
													'escapeTitle' => false,
													'class' => 'btn btn-sm btn-outline-danger',
													'confirm' => __d('queue', 'Sure?'),
													'title' => __d('queue', 'Remove'),
													'block' => true,
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
					<div class="card-footer">
						<?= $this->Html->link(
							__d('queue', 'Detailed Statistics'),
							['controller' => 'QueuedJobs', 'action' => 'stats'],
							['class' => 'text-decoration-none']
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
							<?= $this->Form->postLink(
								'<i class="fas fa-plus me-1"></i>' . h($task),
								['action' => 'addJob', '?' => ['task' => $task]],
								[
									'escapeTitle' => false,
									'class' => 'btn btn-outline-primary btn-sm text-start',
									'confirm' => __d('queue', 'Sure?'),
									'block' => true,
								]
							) ?>
						<?php endforeach; ?>
					</div>
				<?php else: ?>
					<p class="text-muted mb-0"><?= __d('queue', 'No addable tasks available') ?></p>
				<?php endif; ?>
				<hr>
				<p class="small text-muted mb-2"><?= __d('queue', 'Test/Demo Jobs') ?></p>
				<div class="d-grid gap-2">
					<?php foreach ($tasks as $task => $className): ?>
						<?php if (!str_ends_with($task, 'Example')) {
							continue;
						} ?>
						<?= $this->Form->postLink(
							'<i class="fas fa-flask me-1"></i>' . h($task),
							['action' => 'addJob', '?' => ['task' => $task]],
							[
								'escapeTitle' => false,
								'class' => 'btn btn-outline-secondary btn-sm text-start',
								'confirm' => __d('queue', 'Sure?'),
								'block' => true,
							]
						) ?>
					<?php endforeach; ?>
				</div>
				<hr>
				<div class="d-grid gap-2">
					<?= $this->Html->link(
						'<i class="fas fa-clock me-1"></i>' . __d('queue', 'Trigger Delayed Test Job'),
						['controller' => 'QueuedJobs', 'action' => 'test'],
						['class' => 'btn btn-outline-info btn-sm', 'escapeTitle' => false]
					) ?>
					<?php if (Configure::read('debug')): ?>
						<?= $this->Html->link(
							'<i class="fas fa-terminal me-1"></i>' . __d('queue', 'Execute Job'),
							['controller' => 'QueuedJobs', 'action' => 'execute'],
							['class' => 'btn btn-outline-warning btn-sm', 'escapeTitle' => false]
						) ?>
					<?php endif; ?>
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
