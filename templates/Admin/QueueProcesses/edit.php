<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueueProcess $queueProcess
 */
?>
<div class="row">
	<div class="col-lg-8">
		<div class="card">
			<div class="card-header d-flex justify-content-between align-items-center">
				<span><i class="fas fa-edit me-2"></i><?= __d('queue', 'Edit Process') ?> - PID <?= h($queueProcess->pid) ?></span>
				<?= $this->Html->link(
					'<i class="fas fa-arrow-left me-1"></i>' . __d('queue', 'Back'),
					['action' => 'view', $queueProcess->id],
					['class' => 'btn btn-sm btn-outline-secondary', 'escapeTitle' => false]
				) ?>
			</div>
			<div class="card-body">
				<?= $this->Form->create($queueProcess) ?>
				<?= $this->Form->control('server', [
					'class' => 'form-control',
					'label' => __d('queue', 'Server Identifier'),
				]) ?>
				<div class="mt-3">
					<?= $this->Form->button('<i class="fas fa-save me-1"></i>' . __d('queue', 'Save'), ['class' => 'btn btn-primary', 'escapeTitle' => false]) ?>
				</div>
				<?= $this->Form->end() ?>
			</div>
		</div>
	</div>
</div>
