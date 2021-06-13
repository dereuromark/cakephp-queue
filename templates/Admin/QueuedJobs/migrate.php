<?php
/**
 * @var \App\View\AppView $this
 * @var string[] $tasks
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-12" id="actions-sidebar">
	<ul class="side-nav nav nav-pills flex-column">
		<li class="nav-item heading"><?= __d('queue', 'Actions') ?></li>
		<li class="nav-item"><?= $this->Html->link(__d('queue', 'Back'), ['action' => 'index']) ?></li>
	</ul>
</nav>
<div class="content action-form form large-9 medium-8 columns col-sm-8 col-12">
	<h1><?= __d('queue', 'Migrate v3 to v4') ?></h1>

	<?= $this->Form->create() ?>
	<fieldset>
		<legend><?= __d('queue', 'Chose Tasks to migrate') ?></legend>
		<?php
		foreach ($tasks as $name => $fullName) {
			echo $this->Form->control('tasks.' . $name, ['type' => 'checkbox', 'label' => $name . ' => ' . $fullName, 'default' => true]);
		}
		?>
	</fieldset>
	<?= $this->Form->button(__d('queue', 'Submit')) ?>
	<?= $this->Form->end() ?>
</div>
