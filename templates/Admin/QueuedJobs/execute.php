<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="row">
	<div class="col-lg-8">
		<div class="card">
			<div class="card-header">
				<i class="fas fa-terminal me-2"></i><?= __d('queue', 'Add Execute Jobs') ?>
			</div>
			<div class="card-body">
				<?= $this->Form->create(null) ?>
				<?= $this->Form->control('command', [
					'placeholder' => 'bin/cake foo bar --baz',
					'class' => 'form-control font-monospace',
					'label' => __d('queue', 'Command'),
				]) ?>

				<div class="row mt-3">
					<div class="col-md-6">
						<?= $this->Form->control('escape', [
							'type' => 'checkbox',
							'default' => true,
							'class' => 'form-check-input',
							'label' => ['text' => __d('queue', 'Escape command'), 'class' => 'form-check-label'],
						]) ?>
					</div>
					<div class="col-md-6">
						<?= $this->Form->control('log', [
							'type' => 'checkbox',
							'default' => true,
							'class' => 'form-check-input',
							'label' => ['text' => __d('queue', 'Enable logging'), 'class' => 'form-check-label'],
						]) ?>
					</div>
				</div>

				<div class="row mt-3">
					<div class="col-md-6">
						<?= $this->Form->control('exit_code', [
							'placeholder' => '0',
							'default' => '0',
							'class' => 'form-control',
							'label' => __d('queue', 'Expected Exit Code'),
						]) ?>
					</div>
					<div class="col-md-6">
						<?= $this->Form->control('amount', [
							'default' => 1,
							'class' => 'form-control',
							'label' => __d('queue', 'Amount of jobs to spawn'),
						]) ?>
					</div>
				</div>

				<div class="alert alert-warning mt-3">
					<i class="fas fa-shield-alt me-2"></i>
					<?= __d('queue', 'Escaping is recommended to keep on for security.') ?>
				</div>

				<div class="mt-3">
					<?= $this->Form->button('<i class="fas fa-play me-1"></i>' . __d('queue', 'Create Job'), ['class' => 'btn btn-warning', 'escapeTitle' => false]) ?>
				</div>
				<?= $this->Form->end() ?>
			</div>
		</div>
	</div>
</div>
