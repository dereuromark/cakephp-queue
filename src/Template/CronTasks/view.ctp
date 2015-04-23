<div class="page view">
<h2><?php echo __d('queue', 'Cron Task');?></h2>

<h3><?php echo h($cronTask['CronTask']['title']); ?></h3>
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php echo __d('queue', 'Jobtype'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo CronTask::jobtypes($cronTask['CronTask']['jobtype']); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php echo __d('queue', 'Data'); ?></dt>
		<dd>
			<?php echo nl2br(h($cronTask['CronTask']['data'])); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php echo __d('queue', 'Created'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Datetime->niceDate($cronTask['CronTask']['created']); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php echo __d('queue', 'Notbefore'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Datetime->niceDate($cronTask['CronTask']['notbefore']); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php echo __d('queue', 'Completed'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Datetime->niceDate($cronTask['CronTask']['completed']); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php echo __d('queue', 'Failed'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo h($cronTask['CronTask']['failed']); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php echo __d('queue', 'Failure Message'); ?></dt>
		<dd>
			<?php echo nl2br(h($cronTask['CronTask']['failure_message'])); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php echo __d('queue', 'Status'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Format->yesNo($cronTask['CronTask']['status']); ?>
			&nbsp;
		</dd>
	</dl>
</div>

<br /><br />

<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(__d('queue', 'Edit %s', __d('queue', 'Cron Task')), ['action' => 'edit', $cronTask['CronTask']['id']]); ?> </li>
		<li><?php echo $this->Form->postLink(__d('queue', 'Delete %s', __d('queue', 'Cron Task')), ['action' => 'delete', $cronTask['CronTask']['id']], null, __d('queue', 'Are you sure you want to delete # %s?', $cronTask['CronTask']['id'])); ?> </li>
		<li><?php echo $this->Html->link(__d('queue', 'List %s', __d('queue', 'Cron Tasks')), ['action' => 'index']); ?> </li>
	</ul>
</div>