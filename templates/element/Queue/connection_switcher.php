<?php
/**
 * Connection Switcher Element
 *
 * Displays a dropdown for switching between database connections when
 * multi-connection mode is enabled (Queue.connections config array).
 *
 * @var \Cake\View\View $this
 * @var array<string>|null $queueConnections Available connections (null if single connection mode)
 * @var string $queueActiveConnection Currently active connection
 */

// Don't render if multi-connection mode is not enabled
if (empty($queueConnections)) {
	return;
}
?>
<li class="nav-item dropdown">
	<a class="nav-link dropdown-toggle" href="#" id="connectionDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
		<i class="fas fa-database me-1"></i>
		<?= h($queueActiveConnection) ?>
	</a>
	<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="connectionDropdown">
		<li><h6 class="dropdown-header"><?= __d('queue', 'Database Connection') ?></h6></li>
		<?php foreach ($queueConnections as $connection): ?>
			<?php
			$isActive = $connection === $queueActiveConnection;
			$url = $this->Url->build([
				'?' => array_merge(
					$this->getRequest()->getQueryParams(),
					['connection' => $connection]
				),
			]);
			?>
			<li>
				<a class="dropdown-item <?= $isActive ? 'active' : '' ?>" href="<?= $url ?>">
					<?php if ($isActive): ?>
						<i class="fas fa-check me-1"></i>
					<?php endif; ?>
					<?= h($connection) ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</li>
