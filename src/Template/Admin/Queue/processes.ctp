<?php
/**
 * @var \App\View\AppView $this
 * @var array $processes
 * @var \Queue\Model\Entity\QueueProcess[] $terminated
 * @var \Queue\Model\Entity\QueuedJob $queuedJob
 */
use Cake\I18n\Time;

?>


<nav class="col-md-3 col-xs-12 large-3 medium-4 columns" id="actions-sidebar">
	<ul class="side-nav nav nav-pills nav-stacked">
		<li><?= $this->Html->link(__d('queue', 'Dashboard'), ['controller' => 'Queue', 'action' => 'index']) ?></li>
		<li><?php echo $this->Html->link(__d('queue', 'List {0}', __d('queue', 'Queue Processes')), ['controller' => 'QueueProcesses', 'action' => 'index'], ['class' => 'btn margin btn-primary']); ?></li>
	</ul>
</nav>

<div class="content col-md-9 col-xs-12 large-9 medium-8 columns">
<h1><?php echo __d('queue', 'Queue');?></h1>

<h2><?php echo __d('queue', 'Current Queue Processes'); ?></h2>
	<p><?php echo __d('queue', 'Active processes'); ?>:</p>

<ul>
<?php
foreach ($processes as $process => $timestamp) {
	echo '<li>' . $process . ':';
	echo '<ul>';
	echo '<li>Last run: ' . $this->Time->nice(new Time($timestamp)) . '</li>';

	echo '<li>End: ' . $this->Form->postLink(__d('queue', 'Finish current job and end'), ['action' => 'processes', '?' => ['end' => $process]], ['confirm' => 'Sure?', 'class' => 'button secondary btn margin btn-secondary']) . ' (next loop run)</li>';
	if (!$this->Configure->read('Queue.multiserver')) {
		echo '<li>' . __d('queue', 'Kill') . ': ' . $this->Form->postLink(__d('queue', 'Soft kill'), ['action' => 'processes', '?' => ['kill' => $process]], ['confirm' => 'Sure?']) . ' (termination SIGTERM = 15)</li>';
	}

	echo '</ul>';
	echo '</li>';
}
if (empty($processes)) {
	echo 'n/a';
}
?>
</ul>

<?php if (!empty($terminated)) { ?>
	<h3><?php echo __d('queue', 'Terminated') ?></h3>
	<p><?php echo __d('queue', 'These have been marked as to be terminated after finishing this round'); ?>:</p>
	<ul>
	<?php
	foreach ($terminated as $queuedJob) {
		echo '<li>' . $queuedJob->pid;
		echo '</li>';
	}
	?>
	</ul>
<?php } ?>

</div>
