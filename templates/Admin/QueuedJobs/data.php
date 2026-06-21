<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueuedJob $queuedJob
 */
?>
<div class="row">
	<div class="col-lg-10">
		<div class="card">
			<div class="card-header d-flex justify-content-between align-items-center">
				<span><i class="fas fa-database me-2"></i><?= __d('queue', 'Edit Queued Job Payload') ?></span>
				<?= $this->Html->link(
					'<i class="fas fa-arrow-left me-1"></i>' . __d('queue', 'Back'),
					['action' => 'edit', $queuedJob->id],
					['class' => 'btn btn-sm btn-outline-secondary', 'escapeTitle' => false]
				) ?>
			</div>
			<div class="card-body">
				<?= $this->Form->create($queuedJob) ?>
				<?= $this->Form->control('data_string', [
					'rows' => 20,
					'class' => 'form-control font-monospace',
					'label' => __d('queue', 'Payload Data (JSON/Serialized)'),
				]) ?>
				<div class="mt-3">
					<?= $this->Form->button('<i class="fas fa-save me-1"></i>' . __d('queue', 'Save'), ['class' => 'btn btn-primary', 'escapeTitle' => false]) ?>
				</div>
				<?= $this->Form->end() ?>
			</div>
		</div>
	</div>
</div>
