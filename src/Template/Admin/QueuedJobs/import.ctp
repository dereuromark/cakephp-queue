<?php
/**
 * @var \App\View\AppView $this
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
	<ul class="side-nav">
		<li class="heading"><?= __d('queue', 'Actions') ?></li>
		<li><?= $this->Html->link('Back', ['action' => 'index']); ?></li>
	</ul>
</nav>
<div class="releases form large-9 medium-8 columns content">
	<h1>Import</h1>

	<?= $this->Form->create(null, ['type' => 'file']) ?>
	<fieldset>
		<legend><?= __d('queue', 'Import Job from exported JSON') ?></legend>
		<?php
			echo $this->Form->control('file', ['type' => 'file', 'required' => true, 'accept' => '.json']);
			echo $this->Form->control('reset', ['type' => 'checkbox', 'default' => true]);
		?>
	</fieldset>
	<?= $this->Form->button(__d('queue', 'Submit')) ?>
	<?= $this->Form->end() ?>
</div>
