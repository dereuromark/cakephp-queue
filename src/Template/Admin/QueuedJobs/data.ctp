<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueuedJob $queuedJob
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
	<ul class="side-nav nav nav-pills nav-stacked">
		<li class="heading"><?= __('Actions') ?></li>
		<li><?= $this->Html->link(__('Back'), ['action' => 'edit', $queuedJob->id]) ?></li>
		<li><?= $this->Html->link(__('List Queued Jobs'), ['action' => 'index']) ?></li>
	</ul>
</nav>
<div class="content action-form form large-9 medium-8 columns col-sm-8 col-xs-12">
	<?= $this->Form->create($queuedJob) ?>
	<fieldset>
		<legend><?= __('Edit Queued Job Payload') ?></legend>
		<?php
			echo $this->Form->control('data', ['rows' => 20]);
		?>
	</fieldset>
	<?= $this->Form->button(__('Submit')) ?>
	<?= $this->Form->end() ?>
</div>
