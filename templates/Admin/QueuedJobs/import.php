<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="row">
	<div class="col-lg-8">
		<div class="card">
			<div class="card-header">
				<i class="fas fa-file-import me-2"></i><?= __d('queue', 'Import Job') ?>
			</div>
			<div class="card-body">
				<?= $this->Form->create(null, ['type' => 'file']) ?>
				<?= $this->Form->control('file', [
					'type' => 'file',
					'required' => true,
					'accept' => '.json',
					'class' => 'form-control',
					'label' => __d('queue', 'JSON File'),
				]) ?>

				<div class="mt-3">
					<?= $this->Form->control('reset', [
						'type' => 'checkbox',
						'default' => true,
						'class' => 'form-check-input',
						'label' => ['text' => __d('queue', 'Reset job state (clear completed/failed status)'), 'class' => 'form-check-label'],
					]) ?>
				</div>

				<p class="text-muted small mt-3">
					<i class="fas fa-info-circle me-1"></i>
					<?= __d('queue', 'Import a job from a previously exported JSON file.') ?>
				</p>

				<div class="mt-3">
					<?= $this->Form->button('<i class="fas fa-upload me-1"></i>' . __d('queue', 'Import'), ['class' => 'btn btn-primary', 'escapeTitle' => false]) ?>
				</div>
				<?= $this->Form->end() ?>
			</div>
		</div>
	</div>
</div>
