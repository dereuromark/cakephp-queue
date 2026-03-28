<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueuedJob $queuedJob
 * @var string[] $tasks
 */
?>
<div class="row">
	<div class="col-lg-8">
		<div class="card">
			<div class="card-header">
				<i class="fas fa-flask me-2"></i><?= __d('queue', 'Create Test Job') ?>
			</div>
			<div class="card-body">
				<?= $this->Form->create($queuedJob) ?>
				<?= $this->Form->control('job_task', [
					'options' => $tasks,
					'empty' => __d('queue', '-- Select Task --'),
					'class' => 'form-select',
				]) ?>

				<div class="alert alert-info mt-3 mb-3">
					<i class="fas fa-clock me-2"></i>
					<?= __d('queue', 'Current server time') ?>: <strong><?= (new \Cake\I18n\DateTime()) ?></strong>
				</div>

				<?= $this->Form->control('notbefore', [
					'type' => 'datetime-local',
					'value' => (new \Cake\I18n\DateTime())->addMinutes(5)->format('Y-m-d\TH:i'),
					'class' => 'form-control',
					'label' => __d('queue', 'Schedule For (Not Before)'),
				]) ?>

				<p class="text-muted small mt-2">
					<i class="fas fa-info-circle me-1"></i>
					<?= __d('queue', 'The target time must also be in server timezone.') ?>
				</p>

				<div class="mt-3">
					<?= $this->Form->button('<i class="fas fa-play me-1"></i>' . __d('queue', 'Create Job'), ['class' => 'btn btn-primary', 'escapeTitle' => false]) ?>
				</div>
				<?= $this->Form->end() ?>
			</div>
		</div>
	</div>
</div>
