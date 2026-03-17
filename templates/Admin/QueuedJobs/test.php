<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueuedJob $queuedJob
 * @var string[] $tasks
 */
?>
<div class="content action-form form large-9 medium-8 columns col-sm-8 col-12">
	<h1><?= __d('queue', 'Create test jobs') ?></h1>

	<?= $this->Form->create($queuedJob) ?>
	<fieldset>
		<legend><?= __d('queue', 'Trigger Delayed Job') ?></legend>
		<?php
			echo $this->Form->control('job_task', ['options' => $tasks, 'empty' => true]);

			echo '<p>Current (server) time: ' . (new \Cake\I18n\DateTime()) . '</>';

			echo $this->Form->control('notbefore', ['default' => (new \Cake\I18n\DateTime())->addMinutes(5)]);

			echo '<p>The target time must also be in that (server) time(zone).</p>';
		?>
	</fieldset>
	<?= $this->Form->button(__d('queue', 'Submit')) ?>
	<?= $this->Form->end() ?>
</div>
