<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueueProcess $queueProcess
 */
use Queue\Queue\Config;
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
	<ul class="side-nav nav nav-pills nav-stacked">
		<li class="heading"><?= __d('queue', 'Actions') ?></li>
		<li><?= $this->Html->link($this->Format->icon('edit') . ' ' . __d('queue', 'Edit Queue Process'), ['action' => 'edit', $queueProcess->id], ['escape' => false]) ?> </li>

		<?php if (!$queueProcess->terminate) { ?>
			<li><?= $this->Form->postLink($this->Format->icon('close', ['title' => __d('queue', 'Terminate')]). ' ' . __d('queue', 'Terminate (clean remove)'), ['action' => 'terminate', $queueProcess->id], ['escapeTitle' => false, 'confirm' => __d('queue', 'Are you sure you want to terminate # {0}?', $queueProcess->id)]); ?></li>
		<?php } else { ?>
			<li><?php echo $this->Form->postLink($this->Format->icon('delete', ['title' => __d('queue', 'Delete')]). ' ' . __d('queue', 'Delete (not advised)'), ['action' => 'delete', $queueProcess->id], ['escapeTitle' => false, 'confirm' => __d('queue', 'Are you sure you want to delete # {0}?', $queueProcess->id)]); ?></li>
		<?php } ?>

		<li><?= $this->Html->link(__d('queue', 'List {0}', __d('queue', 'Queue Processes')), ['action' => 'index']) ?> </li>
	</ul>
</nav>
<div class="content action-view view large-9 medium-8 columns col-sm-8 col-xs-12">
	<h1>PID <?= h($queueProcess->pid) ?></h1>
	<table class="table vertical-table">
		<tr>
			<th><?= __d('queue', 'Created') ?></th>
			<td>
				<?= $this->Time->nice($queueProcess->created) ?>
				<?php if (!$queueProcess->created->addSeconds(Config::defaultworkertimeout())->isFuture()) {
					echo $this->Format->icon('warning', ['title' => 'Long running (!)']);
				} ?>
			</td>
		</tr>
		<tr>
			<th><?= __d('queue', 'Modified') ?></th>
			<td><?= $this->Time->nice($queueProcess->modified) ?></td>
		</tr>
		<tr>
			<th><?= __d('queue', 'Active') ?></th>
			<td><?= $this->Format->yesNo(!$queueProcess->terminate) ?></td>
		</tr>
		<tr>
			<th><?= __d('queue', 'Server') ?></th>
			<td><?= h($queueProcess->server) ?></td>
		</tr>
		<tr>
			<th><?= __d('queue', 'Workerkey') ?></th>
			<td><?= h($queueProcess->workerkey) ?></td>
		</tr>
	</table>

</div>
