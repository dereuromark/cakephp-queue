<?php
/**
 * This requires chart.js library to be installed and available via layout.
 * You can otherwise just overwrite this template on project level and format/output as your own frontend assets.
 *
 * @var \App\View\AppView $this
 * @var array<string, array<string, float>> $stats
 * @var string[] $jobTypes
 * @var string|null $jobType
 */
?>
<div class="row">
	<div class="col-lg-8">
		<div class="card mb-4">
			<div class="card-header d-flex justify-content-between align-items-center">
				<span>
					<i class="fas fa-chart-line me-2"></i><?= __d('queue', 'Job Statistics') ?>
					<?php if ($jobType): ?>
						<span class="badge bg-secondary ms-2"><?= h($jobType) ?></span>
					<?php endif; ?>
				</span>
				<?php if ($jobType): ?>
					<?= $this->Html->link(
						'<i class="fas fa-arrow-left me-1"></i>' . __d('queue', 'All Jobs'),
						['action' => 'stats', '?' => []],
						['class' => 'btn btn-sm btn-outline-secondary', 'escapeTitle' => false]
					) ?>
				<?php endif; ?>
			</div>
			<div class="card-body">
				<p class="text-muted"><?= __d('queue', 'For already processed jobs - in average seconds per timeframe.') ?></p>

				<div style="position: relative; height: 400px;">
					<canvas id="job-chart"></canvas>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-4">
		<!-- Statistics Navigation -->
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-chart-bar me-2"></i><?= __d('queue', 'Statistics') ?>
			</div>
			<div class="list-group list-group-flush">
				<?= $this->Html->link(
					'<i class="fas fa-chart-line me-2"></i>' . __d('queue', 'Time Series'),
					['action' => 'stats'],
					['class' => 'list-group-item list-group-item-action active', 'escapeTitle' => false]
				) ?>
				<?= $this->Html->link(
					'<i class="fas fa-th me-2"></i>' . __d('queue', 'Heatmap'),
					['action' => 'heatmap'],
					['class' => 'list-group-item list-group-item-action', 'escapeTitle' => false]
				) ?>
				<?= $this->Html->link(
					'<i class="fas fa-arrow-left me-2"></i>' . __d('queue', 'Back to Dashboard'),
					['controller' => 'Queue', 'action' => 'index'],
					['class' => 'list-group-item list-group-item-action', 'escapeTitle' => false]
				) ?>
			</div>
		</div>

		<!-- Filter by Job Type -->
		<div class="card">
			<div class="card-header">
				<i class="fas fa-filter me-2"></i><?= __d('queue', 'Filter by Job Type') ?>
			</div>
			<div class="list-group list-group-flush">
				<?php foreach ($jobTypes as $type): ?>
					<?= $this->Html->link(
						'<i class="fas fa-chart-line me-2"></i>' . h($type),
						['action' => 'stats', '?' => ['job_type' => $type]],
						['class' => 'list-group-item list-group-item-action', 'escapeTitle' => false]
					) ?>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>

<?php
$labels = [];
foreach ($stats as $type => $days) {
	$labels = array_keys($days);
	break;
}

$dataSets = [];
$colors = [
	'rgb(255, 99, 132)',
	'rgb(54, 162, 235)',
	'rgb(255, 206, 86)',
	'rgb(75, 192, 192)',
	'rgb(153, 102, 255)',
];
$colorIndex = 0;
foreach ($stats as $type => $days) {
	$dataSets[] = [
		'label' => $type,
		'data' => array_values($days),
		'borderColor' => $colors[$colorIndex % count($colors)],
		'backgroundColor' => $colors[$colorIndex % count($colors)],
		'fill' => false,
		'tension' => 0.1,
	];
	$colorIndex++;
}
?>

<?php $this->append('script'); ?>
<?php $cspNonce = (string)$this->getRequest()->getAttribute('cspNonce', ''); ?>
<script<?= $cspNonce !== '' ? ' nonce="' . h($cspNonce) . '"' : '' ?>>
document.addEventListener('DOMContentLoaded', function() {
	var chartCanvas = document.getElementById('job-chart');
	if (!chartCanvas) return;

	var data = {
		labels: <?= json_encode($labels, JSON_THROW_ON_ERROR) ?>,
		datasets: <?= json_encode($dataSets, JSON_THROW_ON_ERROR) ?>
	};
	var options = {
		responsive: true,
		maintainAspectRatio: false,
		plugins: {
			legend: {
				position: 'top'
			},
			title: {
				display: true,
				text: <?= json_encode(__d('queue', 'Job Processing Time (seconds)'), JSON_THROW_ON_ERROR) ?>
			}
		},
		scales: {
			y: {
				beginAtZero: true
			}
		}
	};
	new Chart(chartCanvas, {
		type: 'line',
		data: data,
		options: options
	});
});
</script>
<?php $this->end(); ?>
