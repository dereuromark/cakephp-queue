<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueueProcess $queueProcess
 */
use Queue\Queue\Config;
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
	<ul class="side-nav nav nav-pills nav-stacked">
		<li class="heading"><?= __('Actions') ?></li>
		<li><?= $this->Html->link($this->Format->icon('edit') . ' ' . __('Edit Queue Process'), ['action' => 'edit', $queueProcess->id], ['escape' => false]) ?> </li>

		<?php if (!$queueProcess->terminate) { ?>
			<li><?= $this->Form->postLink($this->Format->icon('close', ['title' => __('Terminate')]). ' ' . __('Terminate (clean remove)'), ['action' => 'terminate', $queueProcess->id], ['escapeTitle' => false, 'confirm' => __('Are you sure you want to terminate # {0}?', $queueProcess->id)]); ?></li>
		<?php } else { ?>
			<li><?php echo $this->Form->postLink($this->Format->icon('delete', ['title' => __('Delete')]). ' ' . __('Delete (not advised)'), ['action' => 'delete', $queueProcess->id], ['escapeTitle' => false, 'confirm' => __('Are you sure you want to delete # {0}?', $queueProcess->id)]); ?></li>
		<?php } ?>

		<li><?= $this->Html->link(__('List Queue Processes'), ['action' => 'index']) ?> </li>
	</ul>
</nav>
<div class="content action-view view large-9 medium-8 columns col-sm-8 col-xs-12">
	<h2>PID <?= h($queueProcess->pid) ?></h2>
	<table class="table vertical-table">
		<tr>
			<th><?= __('Created') ?></th>
			<td>
				<?= $this->Time->nice($queueProcess->created) ?>
				<?php if (!$queueProcess->created->addSeconds(Config::defaultworkertimeout())->isFuture()) {
					echo $this->Format->icon('warning', ['title' => 'Long running (!)']);
				} ?>
			</td>
		</tr>
		<tr>
			<th><?= __('Modified') ?></th>
			<td><?= $this->Time->nice($queueProcess->modified) ?></td>
		</tr>
		<tr>
			<th><?= __('Active') ?></th>
			<td><?= $this->Format->yesNo(!$queueProcess->terminate) ?></td>
		</tr>
		<tr>
			<th><?= __('Server') ?></th>
			<td><?= h($queueProcess->server) ?></td>
		</tr>
		<tr>
			<th><?= __('Workerkey') ?></th>
			<td><?= h($queueProcess->workerkey) ?></td>
		</tr>
	</table>

</div>
