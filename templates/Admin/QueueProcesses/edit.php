<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueueProcess $queueProcess
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
	<ul class="side-nav nav nav-pills nav-stacked">
		<li class="heading"><?= __d('queue', 'Actions') ?></li>
		<li><?= $this->Html->link(__d('queue', 'Back'), ['action' => 'view', $queueProcess->id]) ?></li>
		<li><?= $this->Html->link(__d('queue', 'List {0}', __d('queue', 'Queue Processes')), ['action' => 'index']) ?></li>
	</ul>
</nav>
<div class="content action-form form large-9 medium-8 columns col-sm-8 col-xs-12">

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
