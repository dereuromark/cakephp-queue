<?php
/**
 * @var \App\View\AppView $this
 */
use Cake\Core\Configure;
use Cake\I18n\Time;

?>


<nav class="col-md-3 col-xs-12 large-3 medium-4 columns" id="actions-sidebar">
	<ul class="side-nav list-unstyled">
		<li><?php echo $this->Html->link(__d('queue', 'Back'), ['action' => 'index']); ?></li>
	</ul>
</nav>

<div class="col-md-9 col-xs-12 large-9 medium-8 columns">
<h1><?php echo __d('queue', 'Queue');?></h1>

<h2><?php echo __d('queue', 'Current Queue Processes'); ?></h2>
<ul>
<?php
foreach ($processes as $process => $timestamp) {
	echo '<li>' . $process . ':';
	echo '<ul>';
		echo '<li>Last run: ' . $this->Time->nice(new Time($timestamp)) . '</li>';
		echo '<li>Kill: ' . $this->Form->postLink('Soft kill', ['action' => 'processes', '?' => ['kill' => $process]], ['confirm' => 'Sure?']) . ' (next loop run)</li>';
	echo '</ul>';
	echo '</li>';
}
if (empty($processes)) {
	echo 'n/a';
}
?>
</ul>

</div>
