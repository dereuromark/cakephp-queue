<?php
/**
 * Queue Admin Sidebar Navigation
 *
 * @var \Cake\View\View $this
 */

$controller = $this->getRequest()->getParam('controller');
$action = $this->getRequest()->getParam('action');

$isActive = function (string $c, ?array $actions = null) use ($controller, $action): string {
	if ($controller !== $c) {
		return '';
	}
	if ($actions === null) {
		return 'active';
	}

	return in_array($action, $actions, true) ? 'active' : '';
};
?>
<aside class="queue-sidebar d-none d-lg-block">
	<!-- Navigation -->
	<div class="nav-section">
		<div class="nav-section-title"><?= __d('queue', 'Navigation') ?></div>
		<nav class="nav flex-column">
			<a class="nav-link <?= $isActive('Queue', ['index']) ?>" href="<?= $this->Url->build(['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'Queue', 'action' => 'index']) ?>">
				<i class="fas fa-tachometer-alt"></i>
				<?= __d('queue', 'Dashboard') ?>
			</a>
			<a class="nav-link <?= $isActive('QueuedJobs') ?>" href="<?= $this->Url->build(['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'QueuedJobs', 'action' => 'index']) ?>">
				<i class="fas fa-tasks"></i>
				<?= __d('queue', 'Jobs') ?>
			</a>
			<a class="nav-link <?= $isActive('Queue', ['processes']) ?>" href="<?= $this->Url->build(['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'Queue', 'action' => 'processes']) ?>">
				<i class="fas fa-cogs"></i>
				<?= __d('queue', 'Workers (Active)') ?>
			</a>
			<a class="nav-link <?= $isActive('QueueProcesses') ?>" href="<?= $this->Url->build(['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'QueueProcesses', 'action' => 'index']) ?>">
				<i class="fas fa-history"></i>
				<?= __d('queue', 'Processes (History)') ?>
			</a>
		</nav>
	</div>

	<!-- Quick Actions -->
	<div class="nav-section">
		<div class="nav-section-title"><?= __d('queue', 'Quick Actions') ?></div>
		<nav class="nav flex-column">
			<?= $this->Form->postButton(
				'<i class="fas fa-redo"></i> ' . __d('queue', 'Reset Failed'),
				['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'Queue', 'action' => 'reset'],
				[
					'class' => 'nav-link btn btn-link text-start w-100',
					'escapeTitle' => false,
					'form' => [
						'class' => 'd-inline',
						'data-confirm-message' => __d('queue', 'Sure? This will make all failed jobs ready for re-run.'),
					],
				]
			) ?>
			<?= $this->Form->postButton(
				'<i class="fas fa-trash"></i> ' . __d('queue', 'Flush Failed'),
				['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'Queue', 'action' => 'flush'],
				[
					'class' => 'nav-link btn btn-link text-start w-100',
					'escapeTitle' => false,
					'form' => [
						'class' => 'd-inline',
						'data-confirm-message' => __d('queue', 'Sure? This will remove all failed jobs.'),
					],
				]
			) ?>
			<?= $this->Form->postButton(
				'<i class="fas fa-sync"></i> ' . __d('queue', 'Reset All'),
				['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'Queue', 'action' => 'reset', '?' => ['full' => true]],
				[
					'class' => 'nav-link btn btn-link text-start w-100',
					'escapeTitle' => false,
					'form' => [
						'class' => 'd-inline',
						'data-confirm-message' => __d('queue', 'Sure? This will make all failed as well as still running jobs ready for re-run.'),
					],
				]
			) ?>
			<?= $this->Form->postButton(
				'<i class="fas fa-bomb"></i> ' . __d('queue', 'Hard Reset'),
				['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'Queue', 'action' => 'hardReset'],
				[
					'class' => 'nav-link text-warning btn btn-link text-start w-100',
					'escapeTitle' => false,
					'form' => [
						'class' => 'd-inline',
						'data-confirm-message' => __d('queue', 'Sure? This will delete all jobs and completely reset the queue.'),
					],
				]
			) ?>
		</nav>
	</div>
</aside>
