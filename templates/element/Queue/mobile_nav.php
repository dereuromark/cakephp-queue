<?php
/**
 * Queue Admin Mobile Navigation
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
<div class="p-3">
	<!-- Navigation -->
	<div class="mb-4">
		<div class="text-uppercase text-white-50 small mb-2 px-2"><?= __d('queue', 'Navigation') ?></div>
		<nav class="nav flex-column">
			<a class="nav-link text-white py-2 <?= $isActive('Queue', ['index']) ?>" href="<?= $this->Url->build(['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'Queue', 'action' => 'index']) ?>">
				<i class="fas fa-tachometer-alt me-2"></i><?= __d('queue', 'Dashboard') ?>
			</a>
			<a class="nav-link text-white py-2 <?= $isActive('QueuedJobs') ?>" href="<?= $this->Url->build(['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'QueuedJobs', 'action' => 'index']) ?>">
				<i class="fas fa-tasks me-2"></i><?= __d('queue', 'Jobs') ?>
			</a>
			<a class="nav-link text-white py-2 <?= $isActive('Queue', ['processes']) ?>" href="<?= $this->Url->build(['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'Queue', 'action' => 'processes']) ?>">
				<i class="fas fa-cogs me-2"></i><?= __d('queue', 'Workers (Active)') ?>
			</a>
			<a class="nav-link text-white py-2 <?= $isActive('QueueProcesses') ?>" href="<?= $this->Url->build(['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'QueueProcesses', 'action' => 'index']) ?>">
				<i class="fas fa-history me-2"></i><?= __d('queue', 'Processes (History)') ?>
			</a>
		</nav>
	</div>

	<!-- Quick Actions -->
	<div>
		<div class="text-uppercase text-white-50 small mb-2 px-2"><?= __d('queue', 'Quick Actions') ?></div>
		<nav class="nav flex-column">
			<?= $this->Form->postLink(
				'<i class="fas fa-redo me-2"></i>' . __d('queue', 'Reset Failed'),
				['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'Queue', 'action' => 'reset'],
				[
					'class' => 'nav-link text-white py-2',
					'escapeTitle' => false,
					'confirm' => __d('queue', 'Sure? This will make all failed jobs ready for re-run.'),
					'block' => true,
				]
			) ?>
			<?= $this->Form->postLink(
				'<i class="fas fa-trash me-2"></i>' . __d('queue', 'Flush Failed'),
				['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'Queue', 'action' => 'flush'],
				[
					'class' => 'nav-link text-white py-2',
					'escapeTitle' => false,
					'confirm' => __d('queue', 'Sure? This will remove all failed jobs.'),
					'block' => true,
				]
			) ?>
			<?= $this->Form->postLink(
				'<i class="fas fa-sync me-2"></i>' . __d('queue', 'Reset All'),
				['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'Queue', 'action' => 'reset', '?' => ['full' => true]],
				[
					'class' => 'nav-link text-white py-2',
					'escapeTitle' => false,
					'confirm' => __d('queue', 'Sure? This will make all failed as well as still running jobs ready for re-run.'),
					'block' => true,
				]
			) ?>
			<?= $this->Form->postLink(
				'<i class="fas fa-bomb me-2"></i>' . __d('queue', 'Hard Reset'),
				['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'Queue', 'action' => 'hardReset'],
				[
					'class' => 'nav-link text-warning py-2',
					'escapeTitle' => false,
					'confirm' => __d('queue', 'Sure? This will delete all jobs and completely reset the queue.'),
					'block' => true,
				]
			) ?>
		</nav>
	</div>
</div>
