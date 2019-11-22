<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueuedJob $queuedJob
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
	<ul class="side-nav nav nav-pills nav-stacked">
		<li class="heading"><?= __d('queue', 'Actions') ?></li>
		<li><?= $this->Html->link(__d('queue', 'Back'), ['action' => 'view', $queuedJob->id]) ?></li>
		<li><?= $this->Form->postLink(
				__d('queue', 'Delete'),
				['action' => 'delete', $queuedJob->id],
				['confirm' => __d('queue', 'Are you sure you want to delete # {0}?', $queuedJob->id)]
			)
		?></li>
		<li><?= $this->Html->link(__d('queue', 'Edit Payload'), ['action' => 'data', $queuedJob->id]) ?></li>
		<li><?= $this->Html->link(__d('queue', 'List {0}', __d('queue', 'Queued Jobs')), ['action' => 'index']) ?></li>
	</ul>
</nav>
<div class="content action-form form large-9 medium-8 columns col-sm-8 col-xs-12">
	<h1><?= __d('queue', 'Edit') ?></h1>

	<?= $this->Form->create($queuedJob) ?>
	<fieldset>
		<legend><?= __d('queue', 'Edit Queued Job') ?></legend>
		<?php
			echo $this->Form->control('notbefore', ['empty' => true]);
			echo $this->Form->control('priority');
		?>
	</fieldset>
	<?= $this->Form->button(__d('queue', 'Submit')) ?>
	<?= $this->Form->end() ?>
</div>
