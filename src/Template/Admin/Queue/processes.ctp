<?php
use Cake\Core\Configure;
use Cake\I18n\Time;

?>
<div class="page index col-xs-12">
<h2><?php echo __d('queue', 'Queue');?></h2>

<h3><?php echo __d('queue', 'Current Queue Processes'); ?></h3>
<ul>
<?php
foreach ($processes as $process => $timestamp) {
	echo '<li>' . $process . ':';
	echo '<ul>';
		echo '<li>Last run: ' . (new Time($timestamp)) . '</li>';
		echo '<li>Kill: ' . $this->Form->postLink('Kill', ['action' => 'processes', '?' => ['kill' => $process]], ['confirm' => 'Sure?']) . '</li>';
	echo '</ul>';
	echo '</li>';
}
if (empty($processes)) {
	echo 'n/a';
}
?>
</ul>

</div>

<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(__d('queue', 'Back'), ['action' => 'index']); ?></li>
	</ul>
</div>
