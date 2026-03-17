<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueueProcess $queueProcess
 */
use Queue\Queue\Config;
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-12" id="actions-sidebar">
	<ul class="side-nav nav nav-pills flex-column">
		<li class="nav-item heading"><?= __d('queue', 'Actions') ?></li>
		<li class="nav-item"><?= $this->Html->link('<i class="fas fa-edit me-1"></i>' . __d('queue', 'Edit'), ['action' => 'edit', $queueProcess->id], ['escape' => false, 'class' => 'nav-link']) ?></li>
		<?php if (!$queueProcess->terminate): ?>
			<li class="nav-item"><?= $this->Form->postLink('<i class="fas fa-times me-1"></i>' . __d('queue', 'Terminate'), ['action' => 'terminate', $queueProcess->id], ['escapeTitle' => false, 'confirm' => __d('queue', 'Are you sure you want to terminate # {0}?', $queueProcess->id), 'class' => 'nav-link', 'block' => true]) ?></li>
		<?php else: ?>
			<li class="nav-item"><?= $this->Form->postLink('<i class="fas fa-trash me-1"></i>' . __d('queue', 'Delete'), ['action' => 'delete', $queueProcess->id], ['escapeTitle' => false, 'confirm' => __d('queue', 'Are you sure you want to delete # {0}?', $queueProcess->id), 'class' => 'nav-link text-danger', 'block' => true]) ?></li>
		<?php endif; ?>
	</ul>
</nav>
<div class="content action-view view large-9 medium-8 columns col-sm-8 col-12">
	<h1>PID <?= h($queueProcess->pid) ?></h1>
	<table class="table vertical-table">
		<tr>
			<th><?= __d('queue', 'Created') ?></th>
			<td>
				<?= $this->Time->nice($queueProcess->created) ?>
				<?php if (!$queueProcess->created->addSeconds(Config::defaultworkertimeout())->isFuture()) {
					echo '<i class="fas fa-exclamation-triangle text-warning" title="' . __d('queue', 'Long running (!)') . '"></i>';
				} ?>
			</td>
		</tr>
		<tr>
			<th><?= __d('queue', 'Modified') ?></th>
			<td><?= $this->Time->nice($queueProcess->modified) ?></td>
		</tr>
		<tr>
			<th><?= __d('queue', 'Active') ?></th>
			<td>
				<?= $this->element('Queue.yes_no', ['value' => !$queueProcess->terminate]) ?>
				<?php echo !$queueProcess->terminate ? 'Yes' : 'No' ?>
			</td>
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
