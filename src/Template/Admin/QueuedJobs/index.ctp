<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueuedJob[]|\Cake\Collection\CollectionInterface $queuedJobs
 */

use Cake\Core\Configure;
use Cake\Core\Plugin;

?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
	<ul class="side-nav nav nav-pills nav-stacked">
		<li class="heading"><?= __('Actions') ?></li>
		<li><?= $this->Html->link(__('Back'), ['controller' => 'Queue', 'action' => 'index']) ?></li>
		<?php if ($this->Configure->read('debug')) { ?>
		<li><?= $this->Html->link(__('Import'), ['action' => 'import']) ?></li>
		<?php } ?>
	</ul>
</nav>
<div class="content action-index index large-9 medium-8 columns col-sm-8 col-xs-12">

	<?php
	if (Configure::read('Queue.isSearchEnabled') !== false && Plugin::isLoaded('Search')) {
		echo $this->element('Queue.search');
	}
	?>

	<h1><?= __('Queued Jobs') ?></h1>

	<table class="table table-striped">
		<thead>
			<tr>
				<th><?= $this->Paginator->sort('job_type') ?></th>
				<th><?= $this->Paginator->sort('job_group') ?></th>
				<th><?= $this->Paginator->sort('reference') ?></th>
				<th><?= $this->Paginator->sort('created', null, ['direction' => 'desc']) ?></th>
				<th><?= $this->Paginator->sort('notbefore', null, ['direction' => 'desc']) ?></th>
				<th><?= $this->Paginator->sort('fetched', null, ['direction' => 'desc']) ?></th>
				<th><?= $this->Paginator->sort('completed', null, ['direction' => 'desc']) ?></th>
				<th><?= $this->Paginator->sort('progress') ?></th>
				<th><?= $this->Paginator->sort('failed') ?></th>
				<th><?= $this->Paginator->sort('workerkey') ?></th>
				<th><?= $this->Paginator->sort('status') ?></th>
				<th><?= $this->Paginator->sort('priority', null, ['direction' => 'desc']) ?></th>
				<th class="actions"><?= __('Actions') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($queuedJobs as $queuedJob): ?>
			<tr>
				<td><?= h($queuedJob->job_type) ?></td>
				<td><?= h($queuedJob->job_group) ?></td>
				<td>
					<?= h($queuedJob->reference) ?>
					<?php if ($queuedJob->data) {
						echo $this->Format->icon('cubes', ['title' => $this->Text->truncate($queuedJob->data, 1000)]);
					}
					?>
				</td>
				<td><?= $this->Time->nice($queuedJob->created) ?></td>
				<td><?= $this->Time->nice($queuedJob->notbefore) ?></td>
				<td><?= $this->Time->nice($queuedJob->fetched) ?></td>
				<td><?= $this->Time->nice($queuedJob->completed) ?></td>
				<td><?= $this->Number->format($queuedJob->progress) ?></td>
				<td><?= $this->Number->format($queuedJob->failed) ?></td>
				<td><?= h($queuedJob->workerkey) ?></td>
				<td><?= h($queuedJob->status) ?></td>
				<td><?= $this->Number->format($queuedJob->priority) ?></td>
				<td class="actions">
				<?= $this->Html->link($this->Format->icon('view'), ['action' => 'view', $queuedJob->id], ['escapeTitle' => false]); ?>

				<?php if (!$queuedJob->completed) { ?>
					<?= $this->Html->link($this->Format->icon('edit'), ['action' => 'edit', $queuedJob->id], ['escapeTitle' => false]); ?>
				<?php } ?>
				<?= $this->Form->postLink($this->Format->icon('delete'), ['action' => 'delete', $queuedJob->id], ['escapeTitle' => false, 'confirm' => __('Are you sure you want to delete # {0}?', $queuedJob->id)]); ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php echo $this->element('Tools.pagination'); ?>
</div>
