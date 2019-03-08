<?php
/**
 * @var \App\View\AppView $this
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
	<ul class="side-nav">
		<li class="heading"><?= __('Actions') ?></li>
		<li><?= $this->Html->link('Back', ['action' => 'index']); ?></li>
	</ul>
</nav>
<div class="releases form large-9 medium-8 columns content">
	<h2>Import</h2>

	<?= $this->Form->create(null, ['type' => 'file']) ?>
	<fieldset>
		<legend><?= __('Import Job from exported JSON') ?></legend>
		<?php
			echo $this->Form->control('file', ['type' => 'file', 'required' => true, 'accept' => '.json']);
			echo $this->Form->control('reset', ['type' => 'checkbox', 'default' => true]);
		?>
	</fieldset>
	<?= $this->Form->button(__('Submit')) ?>
	<?= $this->Form->end() ?>
</div>
