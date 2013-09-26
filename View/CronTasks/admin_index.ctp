<div class="page index">
<h2><?php echo __('Cron Tasks');?></h2>

<table class="list">
<tr>
	<th><?php echo $this->Paginator->sort('jobtype');?></th>
	<th><?php echo $this->Paginator->sort('title');?></th>
	<th><?php echo $this->Paginator->sort('created');?></th>
	<th><?php echo $this->Paginator->sort('completed');?></th>
	<th><?php echo $this->Paginator->sort('failed');?></th>
	<th><?php echo $this->Paginator->sort('status');?></th>
	<th class="actions"><?php echo __('Actions');?></th>
</tr>
<?php
$i = 0;
foreach ($cronTasks as $cronTask):
	$class = null;
	if ($i++ % 2 == 0) {
		$class = ' class="altrow"';
	}
?>
	<tr<?php echo $class;?>>
		<td>
			<?php echo CronTask::jobtypes($cronTask['CronTask']['jobtype']); ?>
		</td>
		<td>
			<?php echo h($cronTask['CronTask']['title']); ?>
		</td>
		<td>
			<?php echo $this->Datetime->niceDate($cronTask['CronTask']['created']); ?>
		</td>
		<td>
			<?php echo $this->Datetime->niceDate($cronTask['CronTask']['completed']); ?>
		</td>
		<td>
			<?php echo h($cronTask['CronTask']['failed']); ?>
			<br />
			<?php echo nl2br(h($cronTask['CronTask']['failure_message'])); ?>
		</td>
		<td>
			<?php echo $this->Format->yesNo($cronTask['CronTask']['status']); ?>
			<br />
			<?php
				if ($cronTask['CronTask']['notbefore'] > date(FORMAT_DB_DATETIME)) {
					echo $this->Format->cIcon(ICON_WARNING, 'Achtung');
				}
		 	?>
		</td>
		<td class="actions">
			<?php echo $this->Html->link($this->Format->icon('view'), array('action'=>'view', $cronTask['CronTask']['id']), array('escape'=>false)); ?>
			<?php echo $this->Html->link($this->Format->icon('edit'), array('action'=>'edit', $cronTask['CronTask']['id']), array('escape'=>false)); ?>
			<?php echo $this->Form->postLink($this->Format->icon('delete'), array('action'=>'delete', $cronTask['CronTask']['id']), array('escape'=>false), __('Are you sure you want to delete # %s?', $cronTask['CronTask']['id'])); ?>
		</td>
	</tr>
<?php endforeach; ?>
</table>

<div class="pagination-container">
<?php echo $this->element('pagination', array(), array('plugin'=>'tools')); ?></div>

</div>

<br /><br />

<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(__('Add %s', __('Cron Task')), array('action' => 'add')); ?></li>
	</ul>
</div>