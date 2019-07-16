<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueuedJob[] $pendingDetails
 * @var string[] $tasks
 * @var string[] $servers
 * @var array $status
 * @var int $new
 * @var int $current
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
			/** @var \Cake\I18n\FrozenTime $time */
			$time = $status['time'];
			$running = $time->addMinute()->isFuture();
			?>
			<?php echo $this->Format->yesNo($running); ?> <?php echo $running ? __d('queue', 'Running') : __d('queue', 'Not running'); ?> (<?php echo __d('queue', 'last {0}', $this->Time->relLengthOfTime($status['time']))?>)

			<?php
			echo '<div><small>Currently ' . $this->Html->link($status['workers'] . ' worker(s)', ['action' => 'processes']) . ' total.</small></div>';
			?>
			<?php
			echo '<div><small>' . count($servers) . ' CLI server(s): ' . implode(', ', $servers) .'</small></div>';
			?>

		<?php } else { ?>
			n/a
		<?php } ?>

		<h2><?php echo __d('queue', 'Queued Jobs'); ?></h2>
		<p>
		<?php
		echo $new . '/' .$current;
		?> task(s) newly await processing.
		</p>
		<ol>
			<?php
			foreach ($pendingDetails as $queuedJob) {
				echo '<li>' . $this->Html->link($queuedJob->job_type, ['controller' => 'QueuedJobs', 'action' => 'view', $queuedJob->id]) . ' (ref <code>' . h($queuedJob->reference ?: '-') . '</code>, prio ' . $queuedJob->priority . '):';
				echo '<ul>';

				$reset = '';
				if ($queuedJob->failed) {
					$reset = ' ' . $this->Form->postLink('Soft reset', ['action' => 'resetJob', $queuedJob->id], ['confirm' => 'Sure?']);
					$reset .= ' ' . $this->Form->postLink('Remove', ['action' => 'removeJob', $queuedJob->id], ['confirm' => 'Sure?']);
				} elseif ($queuedJob->fetched) {
					$reset .= ' ' . $this->Form->postLink('Remove', ['action' => 'removeJob', $queuedJob->id], ['confirm' => 'Sure?']);
				}

				$notBefore = '';
				if ($queuedJob->notbefore) {
					$notBefore = ' (scheduled ' . $this->Time->nice($queuedJob->notbefore) . ')';
				}

				echo '<li>Created: ' . $this->Time->nice($queuedJob->created) . $notBefore . '</li>';


				if ($queuedJob->fetched) {
					echo '<li>Fetched: ' . $this->Time->nice($queuedJob->fetched) . '</li>';

					$status = '';
					if ($queuedJob->status) {
						$status = ' (status: ' . h($queuedJob->status) . ')';
					}

					echo '<li>Progress: ' . $this->Number->toPercentage($queuedJob->progress * 100, 0) . $status . '</li>';
					echo '<li>Failures: ' . $queuedJob->failed . $reset . '</li>';
					echo '<li>Failure Message: ' . $this->Text->truncate($queuedJob->failure_message, 200) . '</li>';
				}

				echo '</ul>';
				echo '</li>';
			}
			?>
		</ol>

		<h2><?php echo __d('queue', 'Statistics'); ?></h2>
		<ul>
			<?php
			foreach ($data as $row) {
				echo '<li>' . h($row['job_type']) . ':';
				echo '<ul>';
				echo '<li>Finished Jobs in Database: ' . $row['num'] . '</li>';
				echo '<li>Average Job existence: ' . $row['alltime'] . 's</li>';
				echo '<li>Average Execution delay: ' . $row['fetchdelay'] . 's</li>';
				echo '<li>Average Execution time: ' . $row['runtime'] . 's</li>';
				echo '</ul>';
				echo '</li>';
			}
			if (empty($data)) {
				echo 'n/a';
			}
			?>
		</ul>

		<?php if (Configure::read('Queue.isStatisticEnabled')) { ?>
		<p><?php echo $this->Html->link(__d('queue', 'Detailed Statistics'), ['controller' => 'QueuedJobs', 'action' => 'stats']); ?></p>
		<?php } ?>
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
				if (substr($task, -7) !== 'Example') {
					continue;
				}

				echo '<li>';
				echo $this->Form->postLink($task, ['action' => 'addJob', substr($task, 11)], ['confirm' => 'Sure?']);
				echo '</li>';
			}
			?>
		</ul>

		<p><?php echo $this->Html->link(__d('queue', 'Trigger Delayed Test/Demo Job'), ['controller' => 'QueuedJobs', 'action' => 'test']); ?></p>
		<?php if (Configure::read('debug')) { ?>
		<p><?php echo $this->Html->link(__d('queue', 'Trigger Execute Job(s)'), ['controller' => 'QueuedJobs', 'action' => 'execute']); ?></p>
		<?php } ?>
	</div>
</div>

</div>
