<?php
/**
 * @var \App\View\AppView $this
 * @var bool $_isSearch
 */
?>
<div class="queue-search-queued-jobs" style="float: right">
	<?php
	echo $this->Form->create(null, ['valueSources' => 'query']);
	echo $this->Form->control('search', ['placeholder' => 'Auto-wildcard mode', 'label' => 'Search (Jobgroup/Reference)']);
	echo $this->Form->control('job_task', ['empty' => ' - no filter - ']);
	echo $this->Form->control('status', ['options' => \Queue\Model\Entity\QueuedJob::statusesForSearch(), 'empty' => ' - no filter - ']);
	echo $this->Form->button('Filter', ['type' => 'submit']);
	if (!empty($_isSearch)) {
		echo $this->Html->link('Reset', ['action' => 'index'], ['class' => 'button']);
	}
	echo $this->Form->end();
	?>
</div>
