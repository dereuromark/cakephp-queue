<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueueProcess[]|\Cake\Collection\CollectionInterface $queueProcesses
 */
use Queue\Queue\Config;
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
	<ul class="side-nav nav nav-pills nav-stacked">
		<li class="heading"><?= __d('queue', 'Actions') ?></li>
		<li><?= $this->Html->link(__d('queue', 'Dashboard'), ['controller' => 'Queue', 'action' => 'index']) ?></li>
		<li><?= $this->Html->link(__d('queue', 'Back'), ['controller' => 'Queue', 'action' => 'processes'], ['class' => 'btn margin btn-default']) ?></li>
		<li><?= $this->Form->postLink(__d('queue', 'Cleanup'), ['action' => 'cleanup'], ['confirm' => 'Sure to remove all outdated ones (>' . (Config::defaultworkertimeout() * 2) .'s)?', 'class' => 'btn margin btn-warning']) ?></li>
	</ul>
</nav>
<div class="content action-index index large-9 medium-8 columns col-sm-8 col-xs-12">
	<h1><?= __d('queue', 'Queue Processes') ?></h1>
	<table class="table table-striped">
		<thead>
			<tr>
				<th><?= $this->Paginator->sort('pid') ?></th>
				<th><?= $this->Paginator->sort('created', __d('queue', 'Started'), ['direction' => 'desc']) ?></th>
				<th><?= $this->Paginator->sort('modified', __d('queue', 'Last Run'), ['direction' => 'desc']) ?></th>
				<th><?= $this->Paginator->sort('terminate', __d('queue', 'Active')) ?></th>
				<th><?= $this->Paginator->sort('server') ?></th>
				<th class="actions"><?= __d('queue', 'Actions') ?></th>
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
				<td>
					<?= $this->Time->nice($queueProcess->created) ?>
					<?php if (!$queueProcess->created->addSeconds(Config::workermaxruntime())->isFuture()) {
						echo $this->Format->icon('warning', ['title' => 'Long running (!)']);
					} ?>
				</td>
				<td>
					<?php
						$modified = $this->Time->nice($queueProcess->modified);
						if (!$queueProcess->created->addSeconds(Config::defaultworkertimeout())->isFuture()) {
							$modified = '<span class="disabled" title="Beyond default worker timeout!">' . $modified . '</span>';
						}
						echo $modified;
					?>
				</td>
				<td><?= $this->Format->yesNo(!$queueProcess->terminate) ?></td>
				<td><?= h($queueProcess->server) ?></td>
				<td class="actions">
					<?= $this->Html->link($this->Format->icon('view'), ['action' => 'view', $queueProcess->id], ['escapeTitle' => false]); ?>
				<?php if (!$queueProcess->terminate) { ?>
					<?= $this->Form->postLink($this->Format->icon('close', ['title' => __d('queue', 'Terminate')]), ['action' => 'terminate', $queueProcess->id], ['escapeTitle' => false, 'confirm' => __d('queue', 'Are you sure you want to terminate # {0}?', $queueProcess->id)]); ?>
				<?php } ?>

				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php echo $this->element('Tools.pagination'); ?>
</div>
