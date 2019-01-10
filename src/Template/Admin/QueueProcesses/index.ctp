<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueueProcess[]|\Cake\Collection\CollectionInterface $queueProcesses
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
	<ul class="side-nav nav nav-pills nav-stacked">
		<li class="heading"><?= __('Actions') ?></li>
		<li><?= $this->Html->link(__('Back'), ['controller' => 'Queue', 'action' => 'processes'], ['class' => 'btn margin btn-default']) ?></li>
		<li><?= $this->Form->postLink(__('Cleanup'), ['action' => 'cleanup'], ['confirm' => 'Sure to remove all outdated ones (>' . ($this->Configure->readOrFail('Queue.defaultworkertimeout') * 2) .'s)?', 'class' => 'btn margin btn-warning']) ?></li>
	</ul>
</nav>
<div class="content action-index index large-9 medium-8 columns col-sm-8 col-xs-12">
	<h2><?= __('Queue Processes') ?></h2>
	<table class="table table-striped">
		<thead>
			<tr>
				<th><?= $this->Paginator->sort('pid') ?></th>
				<th><?= $this->Paginator->sort('created', __('Started'), ['direction' => 'desc']) ?></th>
				<th><?= $this->Paginator->sort('modified', __('Last Run'), ['direction' => 'desc']) ?></th>
				<th><?= $this->Paginator->sort('terminate', __('Active')) ?></th>
				<th><?= $this->Paginator->sort('server') ?></th>
				<th class="actions"><?= __('Actions') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($queueProcesses as $queueProcess): ?>
			<tr>
				<td>
					<?= h($queueProcess->pid) ?>
					<?php if ($queueProcess->workerkey && $queueProcess->workerkey !== $queueProcess->pid) { ?>
						<div><small><?php echo h($queueProcess->workerkey); ?></small></div>
					<?php } ?>
				</td>
				<td><?= $this->Time->nice($queueProcess->created) ?></td>
				<td><?= $this->Time->nice($queueProcess->modified) ?></td>
				<td><?= $this->Format->yesNo(!$queueProcess->terminate) ?></td>
				<td><?= h($queueProcess->server) ?></td>
				<td class="actions">
				<?php if (!$queueProcess->terminate) { ?>
					<?= $this->Form->postLink($this->Format->icon('close', ['title' => __('Terminate')]), ['action' => 'terminate', $queueProcess->id], ['escapeTitle' => false, 'confirm' => __('Are you sure you want to terminate # {0}?', $queueProcess->id)]); ?>
				<?php } ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php echo $this->element('Tools.pagination'); ?>
</div>
