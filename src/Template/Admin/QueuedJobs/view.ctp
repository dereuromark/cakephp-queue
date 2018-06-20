<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueuedJob $queuedJob
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
    <ul class="side-nav nav nav-pills nav-stacked">
        <li class="heading"><?= __('Actions') ?></li>
		<?php if (!$queuedJob->completed) { ?>
			<li><?= $this->Html->link(__('Edit Queued Job'), ['action' => 'edit', $queuedJob->id]) ?> </li>
		<?php } ?>
        <li><?= $this->Form->postLink(__('Delete Queued Job'), ['action' => 'delete', $queuedJob->id], ['confirm' => __('Are you sure you want to delete # {0}?', $queuedJob->id)]) ?> </li>
        <li><?= $this->Html->link(__('List Queued Jobs'), ['action' => 'index']) ?> </li>
    </ul>
</nav>
<div class="content action-view view large-9 medium-8 columns col-sm-8 col-xs-12">
    <h2><?= h($queuedJob->id) ?></h2>
    <table class="table vertical-table">
            <tr>
            <th><?= __('Job Type') ?></th>
            <td><?= h($queuedJob->job_type) ?></td>
        </tr>
            <tr>
            <th><?= __('Job Group') ?></th>
            <td><?= h($queuedJob->job_group) ?></td>
        </tr>
            <tr>
            <th><?= __('Reference') ?></th>
            <td><?= h($queuedJob->reference) ?></td>
        </tr>
            <tr>
            <th><?= __('Created') ?></th>
            <td><?= $this->Time->nice($queuedJob->created) ?></td>
        </tr>
            <tr>
            <th><?= __('Notbefore') ?></th>
            <td><?= $this->Time->nice($queuedJob->notbefore) ?></td>
        </tr>
            <tr>
            <th><?= __('Fetched') ?></th>
            <td><?= $this->Time->nice($queuedJob->fetched) ?></td>
        </tr>
            <tr>
            <th><?= __('Completed') ?></th>
            <td><?= $this->Time->nice($queuedJob->completed) ?></td>
        </tr>
            <tr>
            <th><?= __('Progress') ?></th>
            <td><?= $this->Number->format($queuedJob->progress) ?></td>
        </tr>
            <tr>
            <th><?= __('Failed') ?></th>
            <td><?= $this->Number->format($queuedJob->failed) ?></td>
        </tr>
            <tr>
            <th><?= __('Workerkey') ?></th>
            <td><?= h($queuedJob->workerkey) ?></td>
        </tr>
            <tr>
            <th><?= __('Status') ?></th>
            <td><?= h($queuedJob->status) ?></td>
        </tr>
            <tr>
            <th><?= __('Priority') ?></th>
            <td><?= $this->Number->format($queuedJob->priority) ?></td>
        </tr>
    </table>
    <div class="row">
        <h3><?= __('Data') ?></h3>
        <?= $this->Text->autoParagraph(h($queuedJob->data)); ?>
    </div>
    <div class="row">
        <h3><?= __('Failure Message') ?></h3>
        <?= $this->Text->autoParagraph(h($queuedJob->failure_message)); ?>
    </div>

</div>
