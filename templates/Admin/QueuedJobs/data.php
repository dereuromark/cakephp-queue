<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueuedJob $queuedJob
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-12" id="actions-sidebar">
	<ul class="side-nav nav nav-pills flex-column">
		<li class="nav-item heading"><?= __d('queue', 'Actions') ?></li>
		<li class="nav-item"><?= $this->Html->link('<i class="fas fa-arrow-left me-1"></i>' . __d('queue', 'Back'), ['action' => 'edit', $queuedJob->id], ['class' => 'nav-link', 'escapeTitle' => false]) ?></li>
	</ul>
</nav>
<div class="content action-form form large-9 medium-8 columns col-sm-8 col-12">
	<h1><?= __d('queue', 'Edit') ?></h1>

	<?= $this->Form->create($queuedJob) ?>
	<fieldset>
		<legend><?= __d('queue', 'Edit Queued Job Payload') ?></legend>
		<?php
			echo $this->Form->control('data_string', ['rows' => 20]);
		?>
	</fieldset>
	<?= $this->Form->button(__d('queue', 'Submit')) ?>
	<?= $this->Form->end() ?>
</div>
