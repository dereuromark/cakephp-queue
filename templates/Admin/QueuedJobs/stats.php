<?php
/**
 * This requires chart.js library to be installed and available via layout.
 * You can otherwise just overwrite this template on project level and format/output as you need it using your own frontend assets.
 *
 * @var \App\View\AppView $this
 * @var array $stats
 * @var string[] $jobTypes
 */
?>

<div class="col-12">
<h1><?= __d('queue', 'Queue') ?></h1>

<div class="row">
	<div class="col-md-8 col-12">

		<h2><?= __d('queue', 'Job Statistics') ?></h2>

		<p><?= __d('queue', 'For already processed jobs - in average seconds per timeframe.') ?></p>

		<canvas id="job-chart" style="height:400px"></canvas>

		<h3 class="mt-4"><?= __d('queue', 'Select a specific job type') ?></h3>
		<ul class="list-group">
			<?php foreach ($jobTypes as $jobType): ?>
				<li class="list-group-item"><?= $this->Html->link('<i class="fas fa-chart-line me-2"></i>' . h($jobType), ['action' => 'stats', $jobType], ['escapeTitle' => false]) ?></li>
			<?php endforeach; ?>
		</ul>
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
	$data = implode(', ', $days);
	$color = $colors[$colorIndex % count($colors)];
	$colorIndex++;

	$dataSets[] = <<<TXT
{
	"label": "$type",
	"data": [$data],
	"borderColor": "$color",
	"backgroundColor": "$color",
	"fill": false,
	"tension": 0.1
}
TXT;
}

?>

<?php $this->append('script'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	var chartCanvas = document.getElementById('job-chart');
	if (!chartCanvas) return;

	var data = {
		"labels": ["<?= implode('", "', $labels) ?>"],
		"datasets": [<?= implode(', ', $dataSets) ?>]
	};
	var options = {
		responsive: true,
		maintainAspectRatio: false,
		plugins: {
			legend: {
				position: 'top',
			},
			title: {
				display: true,
				text: '<?= __d('queue', 'Job Processing Time (seconds)') ?>'
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
