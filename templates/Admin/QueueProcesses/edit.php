<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueueProcess $queueProcess
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-12" id="actions-sidebar">
	<ul class="side-nav nav nav-pills flex-column">
		<li class="nav-item heading"><?= __d('queue', 'Actions') ?></li>
		<li class="nav-item"><?= $this->Html->link('<i class="fas fa-arrow-left me-1"></i>' . __d('queue', 'Back'), ['action' => 'view', $queueProcess->id], ['class' => 'nav-link', 'escapeTitle' => false]) ?></li>
	</ul>
</nav>
<div class="content action-form form large-9 medium-8 columns col-sm-8 col-12">

	<h1><?php echo __d('queue', 'Edit PID {0}', $queueProcess->pid); ?></h1>

	<?= $this->Form->create($queueProcess) ?>
	<fieldset>
		<legend><?= __d('queue', 'Edit Queue Process') ?></legend>
		<?php
			echo $this->Form->control('server');
		?>
	</fieldset>
	<?= $this->Form->button(__d('queue', 'Submit')) ?>
	<?= $this->Form->end() ?>
</div>
