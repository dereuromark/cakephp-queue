<?php

/**
 * Job Status Badge Element
 *
 * @var \Cake\View\View $this
 * @var \Queue\Model\Entity\QueuedJob $job The queued job entity
 */
use Queue\Model\Table\QueuedJobsTable;

$status = 'pending';
$icon = 'clock';

if ($job->completed) {
	$status = 'completed';
	$icon = 'check';
} elseif ($job->status === QueuedJobsTable::STATUS_ABORTED || $job->failure_message) {
	// Terminal: retries exhausted (status = aborted) or a recorded failure.
	// Checked before `fetched` so a job whose worker died on its last attempt
	// shows as Failed, not stuck "Running".
	$status = 'failed';
	$icon = 'times';
} elseif ($job->fetched) {
	$status = 'running';
	$icon = 'spinner fa-spin';
} elseif ($job->notbefore && $job->notbefore->isFuture()) {
	$status = 'scheduled';
	$icon = 'calendar';
}

$statusLabels = [
	'pending' => __d('queue', 'Pending'),
	'running' => __d('queue', 'Running'),
	'completed' => __d('queue', 'Completed'),
	'failed' => __d('queue', 'Failed'),
	'scheduled' => __d('queue', 'Scheduled'),
];
?>
<span class="badge badge-<?= $status ?>">
	<i class="fas fa-<?= $icon ?> me-1"></i>
	<?= $statusLabels[$status] ?>
</span>
