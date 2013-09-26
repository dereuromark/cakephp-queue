<h2><?php echo __('Edit %s', __('Cron Task')); ?></h2>

<div class="page form">
<?php echo $this->Form->create('CronTask');?>
	<fieldset>
		<legend><?php echo __('Edit %s', __('Cron Task')); ?></legend>
	<?php
		echo $this->Form->input('id');
		echo $this->Form->input('jobtype', array('options'=>CronTask::jobtypes()));
		echo $this->Form->input('name', array('after'=>'Plugin.Task / Plugin.Model.method'));

		echo $this->Form->input('title');
		echo $this->Form->input('notbefore', array('dateFormat'=>'DMY', 'timeFormat'=>24));
		echo $this->Form->input('interval', array('after'=>'in Minutes'));
		echo $this->Form->input('status', array('type'=>'checkbox', 'label'=>__('Active')));
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>
</div>

<br /><br />

<div class="actions">
	<ul>
		<li><?php echo $this->Form->postLink(__('Delete'), array('action' => 'delete', $this->Form->value('CronTask.id')), null, __('Are you sure you want to delete # %s?', $this->Form->value('CronTask.id'))); ?></li>
		<li><?php echo $this->Html->link(__('List %s', __('Cron Tasks')), array('action' => 'index'));?></li>
	</ul>
</div>