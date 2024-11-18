<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Queue\Model\Entity\QueuedJob> $queuedJobs
 */

use Brick\VarExporter\VarExporter;
use Cake\Core\Configure;
use Cake\Core\Plugin;

?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-12" id="actions-sidebar">
	<ul class="side-nav nav nav-pills flex-column">
		<li class="nav-item heading"><?= __d('queue', 'Actions') ?></li>
		<li class="nav-item"><?= $this->Html->link(__d('queue', 'Dashboard'), ['controller' => 'Queue', 'action' => 'index']) ?></li>
		<?php if ($this->Configure->read('debug')) { ?>
		<li class="nav-item"><?= $this->Html->link(__d('queue', 'Import'), ['action' => 'import']) ?></li>
		<?php } ?>
	</ul>

	<hr>

	<?= __d('queue', 'Current server time') ?>:
	<br>
	<?php echo $this->Time->nice(new \Cake\I18n\DateTime()); ?>

</nav>
<div class="content action-index index large-9 medium-8 columns col-sm-8 col-12">

	<?php
	if (Configure::read('Queue.isSearchEnabled') !== false && Plugin::isLoaded('Search')) {
		echo $this->element('Queue.search');
	}
	?>

	<h1><?= __d('queue', 'Queued Jobs') ?></h1>

	<table class="table table-striped">
		<thead>
			<tr>
				<th><?= $this->Paginator->sort('job_task') ?></th>
				<th><?= $this->Paginator->sort('job_group') ?></th>
				<th><?= $this->Paginator->sort('reference') ?></th>
				<th><?= $this->Paginator->sort('created', null, ['direction' => 'desc']) ?></th>
				<th><?= $this->Paginator->sort('notbefore', null, ['direction' => 'desc']) ?></th>
				<th><?= $this->Paginator->sort('fetched', null, ['direction' => 'desc']) ?></th>
				<th><?= $this->Paginator->sort('completed', null, ['direction' => 'desc']) ?></th>
				<th><?= $this->Paginator->sort('attempts') ?></th>
				<th><?= $this->Paginator->sort('status') ?></th>
				<th><?= $this->Paginator->sort('priority', null, ['direction' => 'desc']) ?></th>
				<th class="actions"><?= __d('queue', 'Actions') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($queuedJobs as $queuedJob): ?>
			<tr>
				<td><?= h($queuedJob->job_task) ?></td>
				<td><?= h($queuedJob->job_group) ?: '---' ?></td>
				<td>
					<?= h($queuedJob->reference) ?: '---' ?>
					<?php if ($queuedJob->data) {
						$data = $queuedJob->data;
						if ($data && !is_array($data)) {
							$data = json_decode($queuedJob->data, true);
						}
						$data = VarExporter::export($data, VarExporter::TRAILING_COMMA_IN_ARRAY);
						echo $this->Icon->render('cubes', [], ['title' => $this->Text->truncate($data, 1000)]);
					}
					?>
				</td>
				<td><?= $this->Time->nice($queuedJob->created) ?></td>
				<td>
					<?= $this->Time->nice($queuedJob->notbefore) ?>
					<br>
					<?php echo $this->QueueProgress->timeoutProgressBar($queuedJob, 8); ?>
					<?php if ($queuedJob->notbefore && $queuedJob->notbefore->isFuture()) {
						echo '<div><small>';
						echo $this->Time->relLengthOfTime($queuedJob->notbefore);
						echo '</small></div>';
					} ?>
				</td>
				<td>
					<?= $this->Time->nice($queuedJob->fetched) ?>

					<?php if ($queuedJob->fetched) {
						echo '<div><small>';
						echo $this->Time->relLengthOfTime($queuedJob->fetched);
						echo '</small></div>';
					} ?>

					<?php if ($queuedJob->workerkey) { ?>
						<div><small><code><?php echo h($queuedJob->workerkey); ?></code></small></div>
					<?php } ?>
				</td>
				<td>
                    <?= $this->Format->ok($this->Time->nice($queuedJob->completed), (bool)$queuedJob->completed) ?>
                    <?php if ($queuedJob->completed) { ?>
                    <div>
                        <small><?php
                            echo '<span title="Duration">' . $this->Time->duration($queuedJob->completed->diff($queuedJob->fetched)) . '</span>';
                        ?></small>
                    </div>
                    <?php } ?>
                </td>
				<td><?= $this->element('Queue.ok', ['value' => $this->Queue->attempts($queuedJob), 'ok' => $queuedJob->completed || $queuedJob->attempts < 1]); ?></td>
				<td>
					<?= h($queuedJob->status) ?>
					<?php if (!$queuedJob->completed && $queuedJob->fetched) { ?>
						<div>
							<?php if (!$queuedJob->failure_message) { ?>
								<?php echo $this->QueueProgress->progress($queuedJob) ?>
								<br>
								<?php
								$textProgressBar = $this->QueueProgress->progressBar($queuedJob, 8);
								echo $this->QueueProgress->htmlProgressBar($queuedJob, $textProgressBar);
								?>
							<?php } else { ?>
								<i><?php echo $this->Queue->failureStatus($queuedJob); ?></i>
							<?php } ?>
						</div>
					<?php } ?>
				</td>
				<td><?= $this->Number->format($queuedJob->priority) ?></td>
				<td class="actions">
				<?= $this->Html->link($this->Icon->render('view'), ['action' => 'view', $queuedJob->id], ['escapeTitle' => false]); ?>

				<?php if (!$queuedJob->completed) { ?>
					<?= $this->Html->link($this->Icon->render('edit'), ['action' => 'edit', $queuedJob->id], ['escapeTitle' => false]); ?>
				<?php } ?>
				<?= $this->Form->postLink($this->Icon->render('delete'), ['action' => 'delete', $queuedJob->id], ['escapeTitle' => false, 'confirm' => __d('queue', 'Are you sure you want to delete # {0}?', $queuedJob->id)]); ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php echo $this->element('Tools.pagination'); ?>
</div>
