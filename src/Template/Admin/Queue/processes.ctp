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
	<ul class="side-nav list-unstyled">
		<li><?php echo $this->Html->link(__d('queue', 'Back'), ['action' => 'index']); ?></li>
		<li><?php echo $this->Html->link(__d('queue', 'List {0}', __d('queue', 'Queue Processes')), ['controller' => 'QueueProcesses', 'action' => 'index'], ['class' => 'btn margin btn-primary']); ?></li>
	</ul>
</nav>

<div class="col-md-9 col-xs-12 large-9 medium-8 columns">
<h1><?php echo __d('queue', 'Queue');?></h1>

<h2><?php echo __d('queue', 'Current Queue Processes'); ?></h2>
	<p>Active processes:</p>

<ul>
<?php
foreach ($processes as $process => $timestamp) {
	echo '<li>' . $process . ':';
	echo '<ul>';
	echo '<li>Last run: ' . $this->Time->nice(new Time($timestamp)) . '</li>';

	echo '<li>End: ' . $this->Form->postLink('Finish current job and end', ['action' => 'processes', '?' => ['end' => $process]], ['confirm' => 'Sure?']) . ' (next loop run)</li>';
	if (!$this->Configure->read('Queue.multiserver')) {
		echo '<li>Kill: ' . $this->Form->postLink('Soft kill', ['action' => 'processes', '?' => ['kill' => $process]], ['confirm' => 'Sure?']) . ' (termination SIGTERM = 15)</li>';
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
	<h3>Terminated</h3>
	<p>These have been marked as to be terminated after finishing this round:</p>
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
