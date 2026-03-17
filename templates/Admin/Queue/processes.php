<?php
/**
 * @var \App\View\AppView $this
 * @var \Queue\Model\Entity\QueueProcess[] $processes
 * @var \Queue\Model\Entity\QueueProcess[] $terminated
 * @var \Queue\Model\Entity\QueuedJob $queuedJob
 * @var string $key
 */

use Cake\I18n\DateTime;

?>

<h1 class="mb-4">
	<i class="fas fa-cogs me-2 text-primary"></i>
	<?= __d('queue', 'Active Workers') ?>
</h1>

<!-- Active Processes Card -->
<div class="card mb-4">
	<div class="card-header d-flex justify-content-between align-items-center">
		<span><i class="fas fa-microchip me-2"></i><?= __d('queue', 'Current Queue Processes') ?></span>
		<span class="badge bg-primary"><?= count($processes) ?> <?= __d('queue', 'active') ?></span>
	</div>
	<div class="card-body">
		<?php if ($processes): ?>
			<div class="row g-3">
				<?php foreach ($processes as $process): ?>
					<div class="col-md-6">
						<div class="border rounded p-3 h-100">
							<div class="d-flex justify-content-between align-items-start mb-2">
								<h5 class="mb-0">
									<i class="fas fa-terminal me-2 text-secondary"></i>
									PID: <code><?= h($process->pid) ?></code>
								</h5>
								<span class="badge bg-success"><?= __d('queue', 'Active') ?></span>
							</div>

							<div class="mb-3">
								<strong><?= __d('queue', 'Current Job') ?>:</strong>
								<?php if ($process->active_job): ?>
									<?= $this->Html->link(
										h($process->active_job->job_task),
										['controller' => 'QueuedJobs', 'action' => 'view', $process->active_job->id],
										['class' => 'text-decoration-none']
									) ?>
								<?php else: ?>
									<span class="text-muted"><?= __d('queue', 'Idle - waiting for jobs') ?></span>
								<?php endif; ?>
							</div>

							<div class="mb-3 small text-muted">
								<i class="fas fa-clock me-1"></i>
								<?= __d('queue', 'Last activity') ?>: <?= $this->Time->nice(new DateTime($process->modified)) ?>
							</div>

							<div class="d-flex gap-2">
								<?= $this->Form->postLink(
									'<i class="fas fa-stop me-1"></i>' . __d('queue', 'Finish & End'),
									['action' => 'processes', '?' => ['end' => $process->pid]],
									[
										'escapeTitle' => false,
										'class' => 'btn btn-sm btn-outline-warning',
										'confirm' => __d('queue', 'Sure?'),
										'title' => __d('queue', 'Finish current job and end worker'),
										'block' => true,
									]
								) ?>

								<?php if ($process->workerkey === $key || !$this->Configure->read('Queue.multiserver')): ?>
									<?= $this->Form->postLink(
										'<i class="fas fa-skull me-1"></i>' . __d('queue', 'Kill'),
										['action' => 'processes', '?' => ['kill' => $process->pid]],
										[
											'escapeTitle' => false,
											'class' => 'btn btn-sm btn-outline-danger',
											'confirm' => __d('queue', 'Sure? This sends SIGTERM to the process.'),
											'title' => __d('queue', 'Send SIGTERM to terminate immediately'),
											'block' => true,
										]
									) ?>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else: ?>
			<div class="text-center text-muted py-4">
				<i class="fas fa-moon fa-2x mb-2"></i>
				<p class="mb-0"><?= __d('queue', 'No active workers') ?></p>
				<p class="small"><?= __d('queue', 'Start a worker using the CLI command') ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>

<!-- Terminated Processes Card -->
<?php if ($terminated): ?>
	<div class="card mb-4">
		<div class="card-header bg-warning text-dark">
			<i class="fas fa-exclamation-triangle me-2"></i>
			<?= __d('queue', 'Termination Pending') ?>
		</div>
		<div class="card-body">
			<p class="text-muted"><?= __d('queue', 'These workers have been marked for termination after finishing their current job') ?>:</p>
			<ul class="list-group">
				<?php foreach ($terminated as $queueProcess): ?>
					<li class="list-group-item d-flex justify-content-between align-items-center">
						<span>
							<i class="fas fa-hourglass-half me-2 text-warning"></i>
							PID: <code><?= h($queueProcess->pid) ?></code>
						</span>
						<span class="badge bg-warning text-dark"><?= __d('queue', 'Pending termination') ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
<?php endif; ?>

<!-- Process History Link -->
<div class="text-center">
	<?= $this->Html->link(
		'<i class="fas fa-history me-1"></i>' . __d('queue', 'View Process History'),
		['controller' => 'QueueProcesses', 'action' => 'index'],
		['class' => 'btn btn-outline-secondary', 'escapeTitle' => false]
	) ?>
</div>
