<h2><?php echo __d('queue', 'Edit %s', __d('queue', 'Cron Task')); ?></h2>

<div class="page form">
<?php echo $this->Form->create('CronTask');?>
	<fieldset>
		<legend><?php echo __d('queue', 'Edit %s', __d('queue', 'Cron Task')); ?></legend>
	<?php
		echo $this->Form->input('id');
		echo $this->Form->input('jobtype', ['options'=>CronTask::jobtypes()]);
		echo $this->Form->input('name', ['after'=>'Plugin.Task / Plugin.Model.method']);

		echo $this->Form->input('title');
		echo $this->Form->input('notbefore', ['dateFormat'=>'DMY', 'timeFormat'=>24]);
		echo $this->Form->input('interval', ['after'=>'in Minutes']);
		echo $this->Form->input('status', ['type'=>'checkbox', 'label'=>__d('queue', 'Active')]);
	?>
	</fieldset>
<?php echo $this->Form->submit(__d('queue', 'Submit')); echo $this->Form->end();?>
</div>

<br /><br />

<div class="actions">
	<ul>
		<li><?php echo $this->Form->postLink(__d('queue', 'Delete'), ['action' => 'delete', $this->Form->value('CronTask.id')], null, __d('queue', 'Are you sure you want to delete # %s?', $this->Form->value('CronTask.id'))); ?></li>
		<li><?php echo $this->Html->link(__d('queue', 'List %s', __d('queue', 'Cron Tasks')), ['action' => 'index']);?></li>
	</ul>
</div>