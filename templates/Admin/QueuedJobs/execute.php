<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="content action-form form large-9 medium-8 columns col-sm-8 col-12">
	<h1><?= __d('queue', 'Add Execute Jobs') ?></h1>

	<?= $this->Form->create(null) ?>
	<fieldset>
		<legend><?= __d('queue', 'Trigger Execute Job') ?></legend>
		<?php
			echo $this->Form->control('command', ['placeholder' => 'bin/cake foo bar --baz']);
			echo $this->Form->control('escape', ['type' => 'checkbox', 'default' => true]);
			echo $this->Form->control('log', ['type' => 'checkbox', 'default' => true]);
			echo $this->Form->control('exit_code', ['placeholder' => 'Defaults to 0 (success)', 'default' => '0']);

			echo '<p>Escaping is recommended to keep on.</p>';

			echo $this->Form->control('amount', ['default' => 1, 'label' => 'Amount of jobs to spawn']);
		?>
	</fieldset>
	<?= $this->Form->button(__d('queue', 'Submit')) ?>
	<?= $this->Form->end() ?>
</div>
