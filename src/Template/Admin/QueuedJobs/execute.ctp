<?php
/**
 * @var \App\View\AppView $this
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
	<ul class="side-nav nav nav-pills nav-stacked">
		<li class="heading"><?= __('Actions') ?></li>
		<li><?= $this->Html->link(__('List Queued Jobs'), ['action' => 'index']) ?></li>
	</ul>
</nav>
<div class="content action-form form large-9 medium-8 columns col-sm-8 col-xs-12">
	<?= $this->Form->create(null) ?>
	<fieldset>
		<legend><?= __('Trigger Execute Job') ?></legend>
		<?php
			echo $this->Form->control('command', ['placeholder' => 'bin/cake foo bar --baz']);
			echo $this->Form->control('escape', ['type' => 'checkbox', 'default' => true]);

			echo '<p>Escaping is recommended to keep on.</p>';

			echo $this->Form->control('amount', ['default' => 1, 'label' => 'Amount of jobs to spawn']);
		?>
	</fieldset>
	<?= $this->Form->button(__('Submit')) ?>
	<?= $this->Form->end() ?>
</div>
