<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueuedJob $queuedJob
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
    <ul class="side-nav nav nav-pills nav-stacked">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $queuedJob->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $queuedJob->id)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Queued Jobs'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="content action-form form large-9 medium-8 columns col-sm-8 col-xs-12">
    <?= $this->Form->create($queuedJob) ?>
    <fieldset>
        <legend><?= __('Edit Queued Job') ?></legend>
        <?php
            echo $this->Form->control('notbefore', ['empty' => true]);
            echo $this->Form->control('priority');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
