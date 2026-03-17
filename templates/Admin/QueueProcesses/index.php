<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Queue\Model\Entity\QueueProcess> $queueProcesses
 */

use Queue\Queue\Config;

?>

<div class="d-flex justify-content-between align-items-center mb-4">
	<h1 class="mb-0">
		<i class="fas fa-history me-2 text-primary"></i>
		<?= __d('queue', 'Queue Processes') ?>
	</h1>
	<div>
		<?= $this->Html->link(
			'<i class="fas fa-cogs me-1"></i>' . __d('queue', 'Active Workers'),
			['controller' => 'Queue', 'action' => 'processes'],
			['class' => 'btn btn-outline-primary btn-sm', 'escapeTitle' => false]
		) ?>
		<?= $this->Form->postLink(
			'<i class="fas fa-broom me-1"></i>' . __d('queue', 'Cleanup'),
			['action' => 'cleanup'],
			[
				'class' => 'btn btn-outline-warning btn-sm',
				'escapeTitle' => false,
				'confirm' => __d('queue', 'Sure to remove all outdated ones (>{0}s)?', Config::defaultworkertimeout() * 2),
				'block' => true,
			]
		) ?>
	</div>
</div>

<div class="card">
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="table table-hover queue-table mb-0">
				<thead>
					<tr>
						<th><?= $this->Paginator->sort('pid', __d('queue', 'PID')) ?></th>
						<th><?= $this->Paginator->sort('created', __d('queue', 'Started'), ['direction' => 'desc']) ?></th>
						<th><?= $this->Paginator->sort('modified', __d('queue', 'Last Run'), ['direction' => 'desc']) ?></th>
						<th><?= $this->Paginator->sort('terminate', __d('queue', 'Active')) ?></th>
						<th><?= $this->Paginator->sort('server', __d('queue', 'Server')) ?></th>
						<th class="text-end"><?= __d('queue', 'Actions') ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($queueProcesses as $queueProcess): ?>
					<tr>
						<td>
							<code class="fw-bold"><?= h($queueProcess->pid) ?></code>
							<?php if ($queueProcess->workerkey && $queueProcess->workerkey !== $queueProcess->pid): ?>
								<div class="small text-muted">
									<code><?= h($queueProcess->workerkey) ?></code>
								</div>
							<?php endif; ?>
						</td>
						<td>
							<?= $this->Time->nice($queueProcess->created) ?>
							<?php if (!$queueProcess->created->addSeconds(Config::workermaxruntime())->isFuture()): ?>
								<span class="badge bg-warning text-dark ms-1" data-bs-toggle="tooltip" title="<?= __d('queue', 'Long running process') ?>">
									<i class="fas fa-exclamation-triangle"></i>
								</span>
							<?php endif; ?>
						</td>
						<td>
							<?php
							$modified = $this->Time->nice($queueProcess->modified);
							$isStale = !$queueProcess->modified->addSeconds(Config::defaultworkertimeout())->isFuture();
							?>
							<?php if ($isStale): ?>
								<span class="text-danger" data-bs-toggle="tooltip" title="<?= __d('queue', 'Beyond default worker timeout!') ?>">
									<?= $modified ?>
								</span>
							<?php else: ?>
								<?= $modified ?>
							<?php endif; ?>
						</td>
						<td>
							<?= $this->element('Queue.yes_no', ['value' => !$queueProcess->terminate]) ?>
						</td>
						<td>
							<?php if ($queueProcess->server): ?>
								<code><?= h($queueProcess->server) ?></code>
							<?php else: ?>
								<span class="text-muted">---</span>
							<?php endif; ?>
						</td>
						<td class="text-end">
							<div class="btn-group btn-group-sm">
								<?= $this->Html->link(
									'<i class="fas fa-eye"></i>',
									['action' => 'view', $queueProcess->id],
									['escapeTitle' => false, 'class' => 'btn btn-outline-primary', 'title' => __d('queue', 'View')]
								) ?>
								<?php if (!$queueProcess->terminate): ?>
									<?= $this->Form->postLink(
										'<i class="fas fa-times"></i>',
										['action' => 'terminate', $queueProcess->id],
										[
											'escapeTitle' => false,
											'class' => 'btn btn-outline-warning',
											'confirm' => __d('queue', 'Are you sure you want to terminate # {0}?', $queueProcess->id),
											'title' => __d('queue', 'Terminate'),
											'block' => true,
										]
									) ?>
								<?php endif; ?>
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
