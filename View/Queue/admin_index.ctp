<div class="page index">
<h2><?php echo __('Queue');?></h2>

<h3><?php echo __('Status'); ?></h3>
<?php if ($status) { ?>
<?php
	$running = (time() - $status['time']) < MINUTE;
?>
<?php echo $this->Format->yesNo($running); ?> <?php echo $running ? __('Running') : __('Not running'); ?> (<?php echo __('last %s', $this->Datetime->relLengthOfTime($status['time']))?>)

<?php
	echo '<div><small>Currently '.($status['workers']).' worker(s) total.</small></div>';
?>
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
if (empty($data)) {
	echo 'n/a';
}
?>
</ul>

<h3>Settings</h3>
<ul>
<?php
	$configurations = Configure::read('Queue');
	foreach ($configurations as $key => $configuration) {
		echo '<li>';
		if (is_dir($configuration)) {
			$configuration = str_replace(APP, 'APP' . DS, $configuration);
			$configuration = str_replace(DS, '/', $configuration);
		} elseif (is_bool($configuration)) {
			$configuration = $configuration ? 'true' : 'false';
		}
		echo h($key). ': ' . h($configuration);
		echo '</li>';
	}

?>
</ul>
</div>

<div class="actions">
	<ul>
		<li><?php echo $this->Form->postLink(__('Reset %s', __('Queue Tasks')), array('action' => 'reset'), array(), __('Sure? This will completely reset the queue.')); ?></li>
	</ul>
</div>