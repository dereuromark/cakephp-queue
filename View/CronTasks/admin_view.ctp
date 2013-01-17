<div class="page view">
<h2><?php echo __('Cron Task');?></h2>

<h3><?php echo h($cronTask['CronTask']['title']); ?></h3>
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php echo __('Jobtype'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo CronTask::jobtypes($cronTask['CronTask']['jobtype']); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php echo __('Data'); ?></dt>
		<dd>
			<?php echo nl2br(h($cronTask['CronTask']['data'])); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php echo __('Created'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Datetime->niceDate($cronTask['CronTask']['created']); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php echo __('Notbefore'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Datetime->niceDate($cronTask['CronTask']['notbefore']); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php echo __('Completed'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Datetime->niceDate($cronTask['CronTask']['completed']); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php echo __('Failed'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo h($cronTask['CronTask']['failed']); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php echo __('Failure Message'); ?></dt>
		<dd>
			<?php echo nl2br(h($cronTask['CronTask']['failure_message'])); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php echo __('Status'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Format->yesNo($cronTask['CronTask']['status']); ?>
			&nbsp;
		</dd>
	</dl>
</div>

<br /><br />

<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(__('Edit %s', __('Cron Task')), array('action' => 'edit', $cronTask['CronTask']['id'])); ?> </li>
		<li><?php echo $this->Form->postLink(__('Delete %s', __('Cron Task')), array('action' => 'delete', $cronTask['CronTask']['id']), null, __('Are you sure you want to delete # %s?', $cronTask['CronTask']['id'])); ?> </li>
		<li><?php echo $this->Html->link(__('List %s', __('Cron Tasks')), array('action' => 'index')); ?> </li>
	</ul>
</div>