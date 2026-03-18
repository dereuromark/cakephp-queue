<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Queue\Model\Entity\QueuedJob> $queuedJobs
 */

use Brick\VarExporter\VarExporter;
use Cake\Core\Configure;
use Cake\Core\Plugin;

?>

<div class="d-flex justify-content-between align-items-center mb-4">
	<h1 class="mb-0">
		<i class="fas fa-tasks me-2 text-primary"></i>
		<?= __d('queue', 'Queued Jobs') ?>
	</h1>
	<div>
		<?php if ($this->Configure->read('debug')): ?>
			<?= $this->Html->link(
				'<i class="fas fa-file-import me-1"></i>' . __d('queue', 'Import'),
				['action' => 'import'],
				['class' => 'btn btn-outline-secondary btn-sm', 'escapeTitle' => false]
			) ?>
		<?php endif; ?>
	</div>
</div>

<?php
if (Configure::read('Queue.isSearchEnabled') !== false && Plugin::isLoaded('Search')) {
	echo $this->element('Queue.search');
}
?>

<div class="card">
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="table table-hover queue-table mb-0">
				<thead>
					<tr>
						<th><?= $this->Paginator->sort('job_task', __d('queue', 'Task')) ?></th>
						<th><?= $this->Paginator->sort('job_group', __d('queue', 'Group')) ?></th>
						<th><?= $this->Paginator->sort('reference', __d('queue', 'Reference')) ?></th>
						<th><?= $this->Paginator->sort('created', __d('queue', 'Created'), ['direction' => 'desc']) ?></th>
						<th><?= $this->Paginator->sort('notbefore', __d('queue', 'Scheduled'), ['direction' => 'desc']) ?></th>
						<th><?= $this->Paginator->sort('fetched', __d('queue', 'Fetched'), ['direction' => 'desc']) ?></th>
						<th><?= $this->Paginator->sort('completed', __d('queue', 'Completed'), ['direction' => 'desc']) ?></th>
						<th><?= $this->Paginator->sort('attempts', __d('queue', 'Attempts')) ?></th>
						<th><?= $this->Paginator->sort('status', __d('queue', 'Status')) ?></th>
						<th><?= $this->Paginator->sort('priority', __d('queue', 'Priority'), ['direction' => 'desc']) ?></th>
						<th class="text-end"><?= __d('queue', 'Actions') ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($queuedJobs as $queuedJob): ?>
					<tr>
						<td>
							<span class="fw-medium"><?= h($queuedJob->job_task) ?></span>
						</td>
						<td>
							<?php if ($queuedJob->job_group): ?>
								<span class="badge bg-secondary"><?= h($queuedJob->job_group) ?></span>
							<?php else: ?>
								<span class="text-muted">---</span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ($queuedJob->reference): ?>
								<code class="small"><?= h($queuedJob->reference) ?></code>
							<?php else: ?>
								<span class="text-muted">---</span>
							<?php endif; ?>
							<?php if ($queuedJob->data): ?>
								<?php
								$data = $queuedJob->data;
								if ($data && !is_array($data)) {
									$data = json_decode($queuedJob->data, true);
								}
								$dataStr = VarExporter::export($data, VarExporter::TRAILING_COMMA_IN_ARRAY);
								?>
								<span class="badge bg-info text-dark" data-bs-toggle="tooltip" title="<?= h($this->Text->truncate($dataStr, 1000)) ?>">
									<i class="fas fa-database"></i>
								</span>
							<?php endif; ?>
						</td>
						<td class="small text-muted">
							<?= $this->Time->nice($queuedJob->created) ?>
						</td>
						<td class="small">
							<?php if ($queuedJob->notbefore): ?>
								<?= $this->Time->nice($queuedJob->notbefore) ?>
								<?= $this->QueueProgress->timeoutProgressBar($queuedJob, 8) ?>
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
						<td class="small">
							<?php if ($queuedJob->fetched): ?>
								<?= $this->Time->nice($queuedJob->fetched) ?>
								<div class="text-muted">
									<?= method_exists($this->Time, 'relLengthOfTime')
										? $this->Time->relLengthOfTime($queuedJob->fetched)
										: $this->Time->timeAgoInWords($queuedJob->fetched) ?>
								</div>
								<?php if ($queuedJob->workerkey): ?>
									<div><code class="small"><?= h($queuedJob->workerkey) ?></code></div>
								<?php endif; ?>
							<?php else: ?>
								<span class="text-muted">---</span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ($queuedJob->completed): ?>
								<span class="badge badge-completed">
									<i class="fas fa-check me-1"></i>
									<?= $this->Time->nice($queuedJob->completed) ?>
								</span>
								<div class="small text-muted" title="<?= __d('queue', 'Duration') ?>">
									<i class="fas fa-stopwatch me-1"></i>
									<?php
									$interval = $queuedJob->completed->diff($queuedJob->fetched);
									echo method_exists($this->Time, 'duration')
										? $this->Time->duration($interval)
										: ltrim($interval->format('%H:%I:%S'), '0:');
									?>
								</div>
							<?php else: ?>
								<span class="text-muted">---</span>
							<?php endif; ?>
						</td>
						<td>
							<?= $this->element('Queue.ok', [
								'value' => $this->Queue->attempts($queuedJob),
								'ok' => $queuedJob->completed || $queuedJob->attempts < 1,
							]) ?>
						</td>
						<td>
							<?php if ($queuedJob->completed): ?>
								<span class="badge badge-completed">
									<i class="fas fa-check me-1"></i><?= __d('queue', 'Done') ?>
								</span>
							<?php elseif ($queuedJob->failure_message): ?>
								<span class="badge badge-failed">
									<i class="fas fa-times me-1"></i><?= __d('queue', 'Failed') ?>
								</span>
							<?php elseif ($queuedJob->fetched): ?>
								<span class="badge badge-running">
									<i class="fas fa-spinner fa-spin me-1"></i><?= __d('queue', 'Running') ?>
								</span>
								<?php if (!$queuedJob->failure_message): ?>
									<div class="mt-1">
										<?= $this->QueueProgress->progress($queuedJob) ?>
										<br>
										<?= $this->QueueProgress->htmlProgressBar($queuedJob, $this->QueueProgress->progressBar($queuedJob, 8)) ?>
									</div>
								<?php endif; ?>
							<?php elseif ($queuedJob->notbefore && $queuedJob->notbefore->isFuture()): ?>
								<span class="badge badge-scheduled">
									<i class="fas fa-calendar me-1"></i><?= __d('queue', 'Scheduled') ?>
								</span>
							<?php else: ?>
								<span class="badge badge-pending">
									<i class="fas fa-clock me-1"></i><?= __d('queue', 'Pending') ?>
								</span>
							<?php endif; ?>

							<?php if ($queuedJob->status): ?>
								<div class="small text-muted"><?= h($queuedJob->status) ?></div>
							<?php endif; ?>

							<?php if ($queuedJob->memory): ?>
								<div class="small text-muted" title="<?= __d('queue', 'Memory Usage') ?>">
									<i class="fas fa-memory me-1"></i>
									<?= $this->Number->format($queuedJob->memory) ?> MB
								</div>
							<?php endif; ?>
						</td>
						<td>
							<span class="badge bg-secondary"><?= $this->Number->format($queuedJob->priority) ?></span>
						</td>
						<td class="text-end">
							<div class="btn-group btn-group-sm">
								<?= $this->Html->link(
									'<i class="fas fa-eye"></i>',
									['action' => 'view', $queuedJob->id],
									[
										'escapeTitle' => false,
										'class' => 'btn btn-outline-primary',
										'title' => __d('queue', 'View'),
										'aria-label' => __d('queue', 'View'),
									]
								) ?>
								<?php if (!$queuedJob->completed): ?>
									<?= $this->Html->link(
										'<i class="fas fa-edit"></i>',
										['action' => 'edit', $queuedJob->id],
										[
											'escapeTitle' => false,
											'class' => 'btn btn-outline-secondary',
											'title' => __d('queue', 'Edit'),
											'aria-label' => __d('queue', 'Edit'),
										]
									) ?>
								<?php endif; ?>
								<?= $this->Form->postLink(
									'<i class="fas fa-trash"></i>',
									['action' => 'delete', $queuedJob->id],
									[
										'escapeTitle' => false,
										'class' => 'btn btn-outline-danger',
										'confirm' => __d('queue', 'Are you sure you want to delete # {0}?', $queuedJob->id),
										'title' => __d('queue', 'Delete'),
										'aria-label' => __d('queue', 'Delete'),
										'block' => true,
									]
								) ?>
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<div class="card-footer">
		<?= $this->element('Tools.pagination') ?>
	</div>
</div>
