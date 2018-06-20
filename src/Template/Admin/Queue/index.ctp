<?php
/**
 * @var \App\View\AppView $this
 */
use Cake\Core\Configure;
?>


<nav class="col-md-3 col-xs-12 large-3 medium-4 columns" id="actions-sidebar">
	<ul class="side-nav list-unstyled">
		<li><?php echo $this->Form->postLink(__d('queue', 'Reset {0}', __d('queue', 'Queued Jobs')), ['action' => 'reset'], ['confirm' => __d('queue', 'Sure? This will make all failed jobs ready for re-run.'), 'class' => 'btn margin btn-default']); ?></li>
		<li><?php echo $this->Form->postLink(__d('queue', 'Hard Reset {0}', __d('queue', 'Queued Jobs')), ['action' => 'hardReset'], ['confirm' => __d('queue', 'Sure? This will delete all jobs and completely reset the queue.'), 'class' => 'btn margin btn-warning']); ?></li>
		<li><?php echo $this->Html->link(__d('queue', 'List {0}', __d('queue', 'Queued Jobs')), ['controller' => 'QueuedJobs', 'action' => 'index'], ['class' => 'btn margin btn-primary']); ?></li>
	</ul>
</nav>

<div class="col-md-9 col-xs-12 large-9 medium-8 columns">
<h1><?php echo __d('queue', 'Queue');?></h1>

<div class="row">
	<div class="col-md-6 col-xs-12 medium-6 columns">

		<h2><?php echo __d('queue', 'Status'); ?></h2>
		<?php if ($status) { ?>
			<?php
			$running = (time() - $status['time']) < MINUTE;
			?>
			<?php echo $this->Format->yesNo($running); ?> <?php echo $running ? __d('queue', 'Running') : __d('queue', 'Not running'); ?> (<?php echo __d('queue', 'last {0}', $this->Time->relLengthOfTime($status['time']))?>)

			<?php
			echo '<div><small>Currently ' . $this->Html->link($status['workers'] . ' worker(s)', ['action' => 'processes']) . ' total.</small></div>';
			?>
		<?php } else { ?>
			n/a
		<?php } ?>

		<h2><?php echo __d('queue', 'Queued Jobs'); ?></h2>
		<?php
		echo $current;
		?> task(s) await processing

		<ol>
			<?php
			foreach ($pendingDetails as $item) {
				echo '<li>' . $item['job_type'] . ' (' . $item['reference'] . '):';
				echo '<ul>';

				$reset = '';
				if ($item['failed']) {
					$reset = ' ' . $this->Form->postLink('Soft reset', ['action' => 'resetJob', $item['id']], ['confirm' => 'Sure?']);
					$reset .= ' ' . $this->Form->postLink('Remove', ['action' => 'removeJob', $item['id']], ['confirm' => 'Sure?']);
				} elseif ($item['fetched']) {
					$reset .= ' ' . $this->Form->postLink('Remove', ['action' => 'removeJob', $item['id']], ['confirm' => 'Sure?']);
				}

				echo '<li>Created: ' . $this->Time->nice($item['created']) . '</li>';
				echo '<li>Fetched: ' . $this->Time->nice($item['fetched']) . '</li>';
				echo '<li>Status: ' . $item['status'] . '</li>';
				echo '<li>Progress: ' . $this->Number->toPercentage($item['progress'] * 100, 0) . '</li>';
				echo '<li>Failures: ' . $item['failed'] . $reset . '</li>';
				echo '<li>Failure Message: ' . h($item['failure_message']) . '</li>';
				echo '</ul>';
				echo '</li>';
			}
			?>
		</ol>

		<h2><?php echo __d('queue', 'Statistics'); ?></h2>
		<ul>
			<?php
			foreach ($data as $item) {
				echo '<li>' . $item['job_type'] . ':';
				echo '<ul>';
				echo '<li>Finished Jobs in Database: ' . $item['num'] . '</li>';
				echo '<li>Average Job existence: ' . $item['alltime'] . 's</li>';
				echo '<li>Average Execution delay: ' . $item['fetchdelay'] . 's</li>';
				echo '<li>Average Execution time: ' . $item['runtime'] . 's</li>';
				echo '</ul>';
				echo '</li>';
			}
			if (empty($data)) {
				echo 'n/a';
			}
			?>
		</ul>


	</div>

	<div class="col-md-6 col-xs-12 medium-6 columns">

		<h2>Settings</h2>
		Server:
		<ul>
			<li>
				<code>posix</code> extension enabled (optional, recommended): <?php echo $this->Format->yesNo(function_exists('posix_kill')); ?>
			</li>
		</ul>

		Current runtime configuration:
		<ul>
			<?php
			$configurations = Configure::read('Queue');
			foreach ($configurations as $key => $configuration) {
				echo '<li>';
				if (is_dir($configuration)) {
					$configuration = str_replace(ROOT . DS, 'ROOT' . DS, $configuration);
					$configuration = str_replace(DS, '/', $configuration);
				} elseif (is_bool($configuration)) {
					$configuration = $configuration ? 'true' : 'false';
				}
				echo h($key) . ': ' . h($configuration);
				echo '</li>';
			}

			?>
		</ul>

		<h2>Trigger Test/Demo Jobs</h2>
		<ul>
			<?php
			foreach ($tasks as $task) {
				if (substr($task, 0, 11) !== 'Queue.Queue') {
					continue;
				}
				if ($task === 'Queue.QueueExecute') {
					continue;
				}

				echo '<li>';
				echo $this->Form->postLink($task, ['action' => 'addJob', substr($task, 11)], ['confirm' => 'Sure?']);
				echo '</li>';
			}
			?>

		</ul>

	</div>
</div>

</div>
