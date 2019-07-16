<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueueProcess $queueProcess
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
	<ul class="side-nav nav nav-pills nav-stacked">
		<li class="heading"><?= __('Actions') ?></li>
		<li><?= $this->Html->link(__('Back'), ['action' => 'view', $queueProcess->id]) ?></li>
		<li><?= $this->Html->link(__('List Queue Processes'), ['action' => 'index']) ?></li>
	</ul>
</nav>
<div class="content action-form form large-9 medium-8 columns col-sm-8 col-xs-12">

	<h3><?php echo __('Edit PID {0}', $queueProcess->pid); ?></h3>

	<?= $this->Form->create($queueProcess) ?>
	<fieldset>
		<legend><?= __('Edit Queue Process') ?></legend>
		<?php
			echo $this->Form->control('server');
		?>
	</fieldset>
	<?= $this->Form->button(__('Submit')) ?>
	<?= $this->Form->end() ?>
</div>
