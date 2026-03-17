<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueuedJob $queuedJob
 */

use Brick\VarExporter\VarExporter;

?>

<div class="d-flex justify-content-between align-items-center mb-4">
	<h1 class="mb-0">
		<i class="fas fa-clipboard-list me-2 text-primary"></i>
		<?= __d('queue', 'Job') ?> #<?= h($queuedJob->id) ?>
	</h1>
	<div class="btn-group">
		<?php if (!$queuedJob->completed): ?>
			<?= $this->Html->link(
				'<i class="fas fa-edit me-1"></i>' . __d('queue', 'Edit'),
				['action' => 'edit', $queuedJob->id],
				['class' => 'btn btn-outline-primary', 'escapeTitle' => false]
			) ?>
		<?php else: ?>
			<?= $this->Form->postLink(
				'<i class="fas fa-copy me-1"></i>' . __d('queue', 'Clone & Re-run'),
				['action' => 'clone', $queuedJob->id],
				[
					'class' => 'btn btn-outline-success',
					'escapeTitle' => false,
					'confirm' => __d('queue', 'Sure?'),
					'block' => true,
				]
			) ?>
		<?php endif; ?>
		<?= $this->Html->link(
			'<i class="fas fa-download me-1"></i>' . __d('queue', 'Export'),
			['action' => 'view', $queuedJob->id, '_ext' => 'json', '?' => ['download' => true]],
			['class' => 'btn btn-outline-secondary', 'escapeTitle' => false]
		) ?>
		<?= $this->Form->postLink(
			'<i class="fas fa-trash me-1"></i>' . __d('queue', 'Delete'),
			['action' => 'delete', $queuedJob->id],
			[
				'class' => 'btn btn-outline-danger',
				'escapeTitle' => false,
				'confirm' => __d('queue', 'Are you sure you want to delete # {0}?', $queuedJob->id),
				'block' => true,
			]
		) ?>
	</div>
</div>

<div class="row">
	<!-- Main Details -->
	<div class="col-lg-8">
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-info-circle me-2"></i><?= __d('queue', 'Job Details') ?>
			</div>
			<div class="card-body p-0">
				<table class="table table-striped mb-0">
					<tr>
						<th style="width: 200px;"><?= __d('queue', 'Job Type') ?></th>
						<td><span class="fw-medium"><?= h($queuedJob->job_task) ?></span></td>
					</tr>
					<tr>
						<th><?= __d('queue', 'Job Group') ?></th>
						<td>
							<?php if ($queuedJob->job_group): ?>
								<span class="badge bg-secondary"><?= h($queuedJob->job_group) ?></span>
							<?php else: ?>
								<span class="text-muted">---</span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><?= __d('queue', 'Reference') ?></th>
						<td>
							<?php if ($queuedJob->reference): ?>
								<code><?= h($queuedJob->reference) ?></code>
							<?php else: ?>
								<span class="text-muted">---</span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><?= __d('queue', 'Priority') ?></th>
						<td><span class="badge bg-secondary"><?= $this->Number->format($queuedJob->priority) ?></span></td>
					</tr>
					<tr>
						<th><?= __d('queue', 'Workerkey') ?></th>
						<td>
							<?php if ($queuedJob->workerkey): ?>
								<code><?= h($queuedJob->workerkey) ?></code>
								<?php if ($queuedJob->worker_process): ?>
									<?= $this->Html->link(
										'<i class="fas fa-external-link-alt ms-1"></i>',
										['controller' => 'QueueProcesses', 'action' => 'view', $queuedJob->worker_process->id],
										['escapeTitle' => false, 'title' => $queuedJob->worker_process->server ?: $queuedJob->worker_process->pid]
									) ?>
								<?php endif; ?>
							<?php else: ?>
								<span class="text-muted">---</span>
							<?php endif; ?>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<!-- Timeline Card -->
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-clock me-2"></i><?= __d('queue', 'Timeline') ?>
			</div>
			<div class="card-body p-0">
				<table class="table table-striped mb-0">
					<tr>
						<th style="width: 200px;"><?= __d('queue', 'Created') ?></th>
						<td><?= $this->Time->nice($queuedJob->created) ?></td>
					</tr>
					<tr>
						<th><?= __d('queue', 'Scheduled For') ?></th>
						<td>
							<?php if ($queuedJob->notbefore): ?>
								<?= $this->Time->nice($queuedJob->notbefore) ?>
								<?= $this->QueueProgress->timeoutProgressBar($queuedJob, 18) ?>
								<?php if ($queuedJob->notbefore->isFuture()): ?>
									<div class="small text-info">
										<?= method_exists($this->Time, 'relLengthOfTime')
											? $this->Time->relLengthOfTime($queuedJob->notbefore)
											: $this->Time->timeAgoInWords($queuedJob->notbefore) ?>
									</div>
								<?php endif; ?>
							<?php else: ?>
								<span class="text-muted">---</span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><?= __d('queue', 'Fetched') ?></th>
						<td>
							<?php if ($queuedJob->fetched): ?>
								<?= $this->Time->nice($queuedJob->fetched) ?>
								<div class="small text-muted">
									<?= __d('queue', 'Delay') ?>: <?= $this->Time->duration($queuedJob->fetched->diff($queuedJob->created)) ?>
								</div>
							<?php else: ?>
								<span class="text-muted">---</span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><?= __d('queue', 'Completed') ?></th>
						<td>
							<?php if ($queuedJob->completed): ?>
								<span class="badge badge-completed">
									<i class="fas fa-check me-1"></i>
									<?= $this->Time->nice($queuedJob->completed) ?>
								</span>
								<div class="small text-muted">
									<?= __d('queue', 'Duration') ?>: <?= $this->Time->duration($queuedJob->completed->diff($queuedJob->fetched)) ?>
								</div>
							<?php else: ?>
								<span class="text-muted">---</span>
							<?php endif; ?>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<!-- Data Card -->
		<?php if ($queuedJob->data): ?>
			<div class="card mb-4">
				<div class="card-header d-flex justify-content-between align-items-center">
					<span><i class="fas fa-database me-2"></i><?= __d('queue', 'Data') ?></span>
					<?php if (!$queuedJob->completed): ?>
						<?= $this->Html->link(
							'<i class="fas fa-edit me-1"></i>' . __d('queue', 'Edit Data'),
							['action' => 'data', $queuedJob->id],
							['class' => 'btn btn-sm btn-outline-secondary', 'escapeTitle' => false]
						) ?>
					<?php endif; ?>
				</div>
				<div class="card-body">
					<pre class="mb-0"><?php
					$data = $queuedJob->data;
					if ($data && !is_array($data)) {
						$data = json_decode($queuedJob->data, true);
					}
					echo h(VarExporter::export($data, VarExporter::TRAILING_COMMA_IN_ARRAY));
					?></pre>
				</div>
			</div>
		<?php endif; ?>

		<!-- Output Card -->
		<?php if ($queuedJob->output): ?>
			<div class="card mb-4">
				<div class="card-header">
					<i class="fas fa-terminal me-2"></i><?= __d('queue', 'Output') ?>
				</div>
				<div class="card-body">
					<pre class="mb-0"><?= h($queuedJob->output) ?></pre>
				</div>
			</div>
		<?php endif; ?>

		<!-- Failure Message Card -->
		<?php if ($queuedJob->failure_message): ?>
			<div class="card mb-4 border-danger">
				<div class="card-header bg-danger text-white">
					<i class="fas fa-exclamation-triangle me-2"></i><?= __d('queue', 'Failure Message') ?>
				</div>
				<div class="card-body">
					<?= $this->Text->autoParagraph(h($queuedJob->failure_message)) ?>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<!-- Sidebar -->
	<div class="col-lg-4">
		<!-- Status Card -->
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-info me-2"></i><?= __d('queue', 'Status') ?>
			</div>
			<div class="card-body">
				<div class="mb-3">
					<?php if ($queuedJob->completed): ?>
						<span class="badge badge-completed fs-6">
							<i class="fas fa-check me-1"></i><?= __d('queue', 'Completed') ?>
						</span>
					<?php elseif ($queuedJob->failure_message): ?>
						<span class="badge badge-failed fs-6">
							<i class="fas fa-times me-1"></i><?= __d('queue', 'Failed') ?>
						</span>
					<?php elseif ($queuedJob->fetched): ?>
						<span class="badge badge-running fs-6">
							<i class="fas fa-spinner fa-spin me-1"></i><?= __d('queue', 'Running') ?>
						</span>
					<?php elseif ($queuedJob->notbefore && $queuedJob->notbefore->isFuture()): ?>
						<span class="badge badge-scheduled fs-6">
							<i class="fas fa-calendar me-1"></i><?= __d('queue', 'Scheduled') ?>
						</span>
					<?php else: ?>
						<span class="badge badge-pending fs-6">
							<i class="fas fa-clock me-1"></i><?= __d('queue', 'Pending') ?>
						</span>
					<?php endif; ?>

					<?php if ($queuedJob->status): ?>
						<div class="mt-2 text-muted"><?= h($queuedJob->status) ?></div>
					<?php endif; ?>
				</div>

				<!-- Progress -->
				<?php if (!$queuedJob->completed && $queuedJob->fetched && !$queuedJob->failure_message): ?>
					<div class="mb-3">
						<strong><?= __d('queue', 'Progress') ?>:</strong>
						<div class="mt-1">
							<?= $this->QueueProgress->progress($queuedJob) ?>
							<br>
							<?= $this->QueueProgress->htmlProgressBar($queuedJob, $this->QueueProgress->progressBar($queuedJob, 18)) ?>
						</div>
					</div>
				<?php endif; ?>

				<!-- Memory -->
				<?php if ($queuedJob->memory): ?>
					<div class="mb-3">
						<strong><?= __d('queue', 'Memory Usage') ?>:</strong>
						<span class="ms-2"><?= $this->Number->format($queuedJob->memory) ?> MB</span>
					</div>
				<?php endif; ?>

				<!-- Attempts -->
				<div class="mb-3">
					<strong><?= __d('queue', 'Attempts') ?>:</strong>
					<?= $this->element('Queue.ok', [
						'value' => $this->Queue->attempts($queuedJob),
						'ok' => $queuedJob->completed || $queuedJob->attempts < 1,
					]) ?>
				</div>

				<!-- Actions -->
				<?php if ($this->Queue->hasFailed($queuedJob)): ?>
					<hr>
					<?= $this->Form->postLink(
						'<i class="fas fa-redo me-1"></i>' . __d('queue', 'Soft Reset'),
						['controller' => 'Queue', 'action' => 'resetJob', $queuedJob->id],
						[
							'class' => 'btn btn-primary w-100',
							'escapeTitle' => false,
							'confirm' => __d('queue', 'Sure?'),
							'block' => true,
						]
					) ?>
				<?php elseif (!$queuedJob->completed && $queuedJob->fetched && $queuedJob->attempts && $queuedJob->failure_message): ?>
					<hr>
					<?= $this->Form->postLink(
						'<i class="fas fa-redo me-1"></i>' . __d('queue', 'Force Reset'),
						['controller' => 'Queue', 'action' => 'resetJob', $queuedJob->id],
						[
							'class' => 'btn btn-warning w-100',
							'escapeTitle' => false,
							'confirm' => __d('queue', 'Sure? This job is currently waiting to be re-queued.'),
							'block' => true,
						]
					) ?>
				<?php endif; ?>
			</div>
		</div>

		<!-- Navigation Card -->
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-arrow-circle-left me-2"></i><?= __d('queue', 'Navigation') ?>
			</div>
			<div class="list-group list-group-flush">
				<?= $this->Html->link(
					'<i class="fas fa-list me-2"></i>' . __d('queue', 'Back to Jobs List'),
					['action' => 'index'],
					['class' => 'list-group-item list-group-item-action', 'escapeTitle' => false]
				) ?>
				<?= $this->Html->link(
					'<i class="fas fa-tachometer-alt me-2"></i>' . __d('queue', 'Dashboard'),
					['controller' => 'Queue', 'action' => 'index'],
					['class' => 'list-group-item list-group-item-action', 'escapeTitle' => false]
				) ?>
			</div>
		</div>
	</div>
</div>
