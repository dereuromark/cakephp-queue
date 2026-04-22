<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueueProcess $queueProcess
 */
use Queue\Queue\Config;
?>
<div class="row">
	<div class="col-lg-8">
		<div class="card mb-4">
			<div class="card-header d-flex justify-content-between align-items-center">
				<span><i class="fas fa-microchip me-2"></i><?= __d('queue', 'Process Details') ?></span>
				<span class="badge bg-secondary">PID <?= h($queueProcess->pid) ?></span>
			</div>
			<div class="card-body p-0">
				<table class="table table-striped mb-0">
					<tr>
						<th style="width: 200px;"><?= __d('queue', 'Created') ?></th>
						<td>
							<?= $this->Time->nice($queueProcess->created) ?>
							<?php if (!$queueProcess->created->addSeconds(Config::defaultworkertimeout())->isFuture()): ?>
								<span class="badge bg-warning text-dark ms-2">
									<i class="fas fa-exclamation-triangle me-1"></i><?= __d('queue', 'Long running') ?>
								</span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><?= __d('queue', 'Last Modified') ?></th>
						<td><?= $this->Time->nice($queueProcess->modified) ?></td>
					</tr>
					<tr>
						<th><?= __d('queue', 'Status') ?></th>
						<td>
							<?php if (!$queueProcess->terminate): ?>
								<span class="badge bg-success">
									<i class="fas fa-check-circle me-1"></i><?= __d('queue', 'Active') ?>
								</span>
							<?php else: ?>
								<span class="badge bg-danger">
									<i class="fas fa-times-circle me-1"></i><?= __d('queue', 'Terminated') ?>
								</span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><?= __d('queue', 'Server') ?></th>
						<td>
							<?php if ($queueProcess->server): ?>
								<code><?= h($queueProcess->server) ?></code>
							<?php else: ?>
								<span class="text-muted">---</span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><?= __d('queue', 'Worker Key') ?></th>
						<td>
							<?php if ($queueProcess->workerkey): ?>
								<code><?= h($queueProcess->workerkey) ?></code>
							<?php else: ?>
								<span class="text-muted">---</span>
							<?php endif; ?>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>

	<div class="col-lg-4">
		<div class="card">
			<div class="card-header">
				<i class="fas fa-cogs me-2"></i><?= __d('queue', 'Actions') ?>
			</div>
			<div class="list-group list-group-flush">
				<?= $this->Html->link(
					'<i class="fas fa-edit me-2"></i>' . __d('queue', 'Edit Process'),
					['action' => 'edit', $queueProcess->id],
					['class' => 'list-group-item list-group-item-action', 'escapeTitle' => false]
				) ?>
				<?php if (!$queueProcess->terminate): ?>
					<?= $this->Form->postButton(
						'<i class="fas fa-times me-2"></i>' . __d('queue', 'Terminate (Graceful)'),
						['action' => 'terminate', $queueProcess->id],
						[
							'class' => 'list-group-item list-group-item-action text-warning btn btn-link text-start w-100',
							'escapeTitle' => false,
							'form' => [
								'class' => 'd-inline',
								'data-confirm-message' => __d('queue', 'Are you sure you want to terminate # {0}?', $queueProcess->id),
							],
						]
					) ?>
				<?php else: ?>
					<?= $this->Form->postButton(
						'<i class="fas fa-trash me-2"></i>' . __d('queue', 'Delete (Force)'),
						['action' => 'delete', $queueProcess->id],
						[
							'class' => 'list-group-item list-group-item-action text-danger btn btn-link text-start w-100',
							'escapeTitle' => false,
							'form' => [
								'class' => 'd-inline',
								'data-confirm-message' => __d('queue', 'Are you sure you want to delete # {0}?', $queueProcess->id),
							],
						]
					) ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
