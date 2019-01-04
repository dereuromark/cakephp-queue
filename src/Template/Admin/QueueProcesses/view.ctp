<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $queueProcess
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
    <ul class="side-nav nav nav-pills nav-stacked">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Queue Process'), ['action' => 'edit', $queueProcess->id]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Queue Process'), ['action' => 'delete', $queueProcess->id], ['confirm' => __('Are you sure you want to delete # {0}?', $queueProcess->id)]) ?> </li>
        <li><?= $this->Html->link(__('List Queue Processes'), ['action' => 'index']) ?> </li>
    </ul>
</nav>
<div class="content action-view view large-9 medium-8 columns col-sm-8 col-xs-12">
    <h2>PID <?= h($queueProcess->pid) ?></h2>
    <table class="table vertical-table">
                <tr>
            <th><?= __('Created') ?></th>
            <td><?= $this->Time->nice($queueProcess->created) ?></td>
        </tr>
            <tr>
            <th><?= __('Modified') ?></th>
            <td><?= $this->Time->nice($queueProcess->modified) ?></td>
        </tr>
            <tr>
            <th><?= __('Terminate') ?></th>
            <td><?= $this->Format->yesNo($queueProcess->terminate) ?></td>
        </tr>
            <tr>
            <th><?= __('Server') ?></th>
            <td><?= h($queueProcess->server) ?></td>
        </tr>
    </table>

</div>
