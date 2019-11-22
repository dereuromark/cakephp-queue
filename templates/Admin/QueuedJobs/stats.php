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

<nav class="col-md-3 col-xs-12 large-3 medium-4 columns" id="actions-sidebar">
	<ul class="side-nav nav nav-pills nav-stacked">
		<li class="heading"><?= __d('queue', 'Actions') ?></li>
		<li><?= $this->Html->link(__d('queue', 'Dashboard'), ['controller' => 'Queue', 'action' => 'index']) ?></li>
		<li><?php echo $this->Html->link(__d('queue', 'List {0}', __d('queue', 'Queued Jobs')), ['controller' => 'QueuedJobs', 'action' => 'index'], ['class' => 'btn margin btn-primary']); ?></li>
	</ul>
</nav>

<div class="col-md-9 col-xs-12 large-9 medium-8 columns">
<h1><?php echo __d('queue', 'Queue');?></h1>

<div class="row">
	<div class="col-md-6 col-xs-12 medium-6 columns">

		<h2><?php echo __d('queue', 'Job Statistics'); ?></h2>

		<p>For already processed jobs - in average seconds per timeframe.</p>

		<canvas id="job-chart" style="height:400px"></canvas>


		<h3>Select a specific job type</h3>
		<ul>
			<?php foreach ($jobTypes as $jobType) { ?>
				<li><?php echo $this->Html->link($jobType, ['action' => 'stats', $jobType]); ?></li>
			<?php } ?>
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
foreach ($stats as $type => $days) {
	$data = implode(', ', $days);

	$dataSets[] = <<<TXT
{
	"label": "$type",
	"data": [
		$data
	],
	"borderColor": [
		"rgb(255, 99, 132)"
	],
	"fill": false
}
TXT;

}

?>

<?php $this->append('script');?>
<?php
// see http://www.chartjs.org/docs/latest/charts/line.html
?>
<script>
	var chartCanvas = $('#job-chart');

	var data  = {
		"labels": ["<?php echo implode('", "', $labels) ?>"],
		"datasets": [<?php echo implode(', ', $dataSets) ?>]
	};
	var options = {
	};
	var chart = new Chart(chartCanvas, {
		type: 'line',
		data: data,
		options: options
	});

</script>
<?php $this->end();?>
