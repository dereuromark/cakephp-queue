<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueuedJob $queuedJob
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
    <ul class="side-nav nav nav-pills nav-stacked">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('List Queued Jobs'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="content action-form form large-9 medium-8 columns col-sm-8 col-xs-12">
    <?= $this->Form->create($queuedJob) ?>
    <fieldset>
        <legend><?= __('Trigger Delayed Job') ?></legend>
        <?php
			echo $this->Form->control('job_type', ['options' => $tasks, 'empty' => true]);

			echo '<p>Current (server) time: ' . (new \Cake\I18n\FrozenTime()) . '</>';

			echo $this->Form->control('notbefore', ['default' => (new \Cake\I18n\FrozenTime())->addMinutes(5)]);

			echo '<p>The target time must also be in that (server) time(zone).</p>';
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
