<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $_isSearch
 */
?>
<div class="queue-search-queued-jobs" style="float: right">
	<?php
	echo $this->Form->create(null, ['valueSources' => 'query']);
	echo $this->Form->control('search', ['placeholder' => 'Auto-wildcard mode', 'label' => 'Search (Jobgroup/Reference)']);
	echo $this->Form->control('job_type', ['empty' => ' - no filter - ']);
	echo $this->Form->control('status', ['options' => ['completed' => 'Completed', 'in_progress' => 'In Progress'], 'empty' => ' - no filter - ']);
	echo $this->Form->button('Filter', ['type' => 'submit']);
	if (!empty($_isSearch)) {
		echo $this->Html->link('Reset', ['action' => 'index'], ['class' => 'button']);
	}
	echo $this->Form->end();
	?>
</div>
