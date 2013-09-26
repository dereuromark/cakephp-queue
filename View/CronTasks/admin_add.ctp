<h2><?php echo __('Add %s', __('Cron Task')); ?></h2>

<div class="page form">
<?php echo $this->Form->create('CronTask');?>
	<fieldset>
		<legend><?php echo __('Add %s', __('Cron Task')); ?></legend>
	<?php
		echo $this->Form->input('jobtype', array('options'=>CronTask::jobtypes()));
		echo $this->Form->input('name', array('after'=>'Plugin.Task / Plugin.Model.method'));

		echo $this->Form->input('title');
		echo $this->Form->input('notbefore', array('dateFormat'=>'DMY', 'timeFormat'=>24));
		echo $this->Form->input('interval', array('after'=>'in Minutes'));
		echo $this->Form->input('status', array('type'=>'checkbox', 'label'=>__('Active')));
		//echo $this->Form->input('reference');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>
</div>

<br /><br />

<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(__('List %s', __('Cron Tasks')), array('action' => 'index'));?></li>
	</ul>
</div>