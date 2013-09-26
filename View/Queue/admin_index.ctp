<div class="page index">
<h2><?php echo __('Queue');?></h2>

<h3><?php echo __('Status'); ?></h3>
<?php if ($status) { ?>
<?php
	$running = (time() - $status) < MINUTE;
?>
<?php echo $this->Format->yesNo($running); ?> <?php echo $running ? __('Running') : __('Not running'); ?> (<?php echo __('last %s', $this->Datetime->relLengthOfTime($status))?>)
<?php } else { ?>
n/a
<?php } ?>

<h3><?php echo __('Queued Tasks'); ?></h3>
<?php
 echo $current;
?> task(s) await processing


<h3><?php echo __('Statistics'); ?></h3>
<ul>
<?php
foreach ($data as $item) {
	echo '<li>'.$item['QueuedTask']['jobtype'] . ":";
	echo '<ul>';
		echo '<li>Finished Jobs in Database: '.$item[0]['num'].'</li>';
		echo '<li>Average Job existence: '.$item[0]['alltime'].'s</li>';
		echo '<li>Average Execution delay: '.$item[0]['fetchdelay'].'s</li>';
		echo '<li>Average Execution time: '.$item[0]['runtime'].'s</li>';
	echo '</ul>';
	echo '</li>';
}
?>
</ul>

</div>

<div class="actions">
	<ul>
		<li><?php echo $this->Form->postLink(__('Reset %s', __('All')), array('action' => 'reset'), array(), __('Sure?')); ?></li>
	</ul>
</div>