<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueuedJob $queuedJob
 */
?>
<div class="row">
	<div class="col-lg-8">
		<div class="card">
			<div class="card-header d-flex justify-content-between align-items-center">
				<span><i class="fas fa-edit me-2"></i><?= __d('queue', 'Edit Queued Job') ?></span>
				<?= $this->Html->link(
					'<i class="fas fa-arrow-left me-1"></i>' . __d('queue', 'Back'),
					['action' => 'view', $queuedJob->id],
					['class' => 'btn btn-sm btn-outline-secondary', 'escapeTitle' => false]
				) ?>
			</div>
			<div class="card-body">
				<?= $this->Form->create($queuedJob) ?>
				<?= $this->Form->control('notbefore', [
					'type' => 'text',
					'value' => $queuedJob->notbefore?->format('Y-m-d H:i'),
					'class' => 'form-control flatpickr-datetime',
				]) ?>
				<?= $this->Form->control('priority', ['class' => 'form-control']) ?>
				<div class="mt-3">
					<?= $this->Form->button('<i class="fas fa-save me-1"></i>' . __d('queue', 'Save'), ['class' => 'btn btn-primary', 'escapeTitle' => false]) ?>
				</div>
				<?= $this->Form->end() ?>
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
					'<i class="fas fa-database me-2"></i>' . __d('queue', 'Edit Payload'),
					['action' => 'data', $queuedJob->id],
					['class' => 'list-group-item list-group-item-action', 'escapeTitle' => false]
				) ?>
				<?= $this->Form->postLink(
					'<i class="fas fa-trash me-2"></i>' . __d('queue', 'Delete Job'),
					['action' => 'delete', $queuedJob->id],
					[
						'class' => 'list-group-item list-group-item-action text-danger',
						'escapeTitle' => false,
						'confirm' => __d('queue', 'Are you sure you want to delete # {0}?', $queuedJob->id),
						'block' => true,
					]
				) ?>
			</div>
		</div>
	</div>
</div>
