<h2><?php echo __('Queue');?> - Admin Backend</h2>

<h3>Status</h3>
<?php
$isRunning = time()-$details['worker'] < MINUTE;
echo $this->Format->yesNo($isRunning).' ';
if ($isRunning) {
	echo __('Worker is Running');
} else {
	echo __('Worker is NOT Running');
}
?>
<br />
Last Time: <?php echo $this->Datetime->niceDate($details['worker'], FORMAT_NICE_YMDHMS); ?> (<?php echo $this->Datetime->relLengthOfTime($details['worker']); ?>)

<h3>Tasks</h3>
<ul>
<?php
	foreach ($tasks as $type => $count) {
		echo '<li>';
		echo str_pad($type, 20, ' ', STR_PAD_RIGHT) . ": " . $count;
		echo '</li>';
	}
?>
</ul>
Total: <?php echo $allTasks; ?>
