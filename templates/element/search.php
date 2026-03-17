<?php
/**
 * @var \App\View\AppView $this
 * @var bool $_isSearch
 */
?>
<div class="card mb-4">
	<div class="card-header">
		<i class="fas fa-search me-2"></i><?= __d('queue', 'Search & Filter') ?>
	</div>
	<div class="card-body">
		<?= $this->Form->create(null, ['valueSources' => 'query', 'class' => 'row g-3']) ?>
		<div class="col-md-4">
			<?= $this->Form->control('search', [
				'placeholder' => __d('queue', 'Auto-wildcard mode'),
				'label' => __d('queue', 'Search (Group/Reference)'),
				'class' => 'form-control',
			]) ?>
		</div>
		<div class="col-md-3">
			<?= $this->Form->control('job_task', [
				'empty' => __d('queue', '-- All Tasks --'),
				'class' => 'form-select',
				'label' => __d('queue', 'Task'),
			]) ?>
		</div>
		<div class="col-md-3">
			<?= $this->Form->control('status', [
				'options' => \Queue\Model\Entity\QueuedJob::statusesForSearch(),
				'empty' => __d('queue', '-- All Statuses --'),
				'class' => 'form-select',
				'label' => __d('queue', 'Status'),
			]) ?>
		</div>
		<div class="col-md-2 d-flex align-items-end gap-2">
			<?= $this->Form->button('<i class="fas fa-filter me-1"></i>' . __d('queue', 'Filter'), [
				'type' => 'submit',
				'class' => 'btn btn-primary',
				'escapeTitle' => false,
			]) ?>
			<?php if (!empty($_isSearch)): ?>
				<?= $this->Html->link(
					'<i class="fas fa-times"></i>',
					['action' => 'index'],
					['class' => 'btn btn-outline-secondary', 'escapeTitle' => false, 'title' => __d('queue', 'Reset')]
				) ?>
			<?php endif; ?>
		</div>
		<?= $this->Form->end() ?>
	</div>
</div>
