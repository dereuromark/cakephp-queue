<?php
/**
 * @var \App\View\AppView $this
 * @var array{grid: array<int, array<int, int>>, summary: array<string, mixed>} $heatmapData
 * @var string[] $jobTypes
 * @var string|null $jobType
 * @var string $metric
 * @var int $days
 */

$grid = $heatmapData['grid'];
$summary = $heatmapData['summary'];
$dayNames = [__d('queue', 'Sun'), __d('queue', 'Mon'), __d('queue', 'Tue'), __d('queue', 'Wed'), __d('queue', 'Thu'), __d('queue', 'Fri'), __d('queue', 'Sat')];
$dayNamesFull = [__d('queue', 'Sunday'), __d('queue', 'Monday'), __d('queue', 'Tuesday'), __d('queue', 'Wednesday'), __d('queue', 'Thursday'), __d('queue', 'Friday'), __d('queue', 'Saturday')];

// Find max value for color scaling
$maxValue = 1;
foreach ($grid as $hours) {
	$maxValue = max($maxValue, max($hours));
}
?>

<div class="row">
	<div class="col-lg-9">
		<!-- Summary Card -->
		<div class="card mb-4">
			<div class="card-header d-flex justify-content-between align-items-center">
				<span>
					<i class="fas fa-chart-area me-2"></i><?= __d('queue', 'Throughput Summary') ?>
					<?php if ($jobType) { ?>
						<span class="badge bg-secondary ms-2"><?= h($jobType) ?></span>
					<?php } ?>
				</span>
				<span class="badge bg-info"><?= __d('queue', 'Last {0} days', $days) ?></span>
			</div>
			<div class="card-body">
				<div class="row text-center">
					<div class="col-md-2 col-sm-4 mb-3">
						<div class="h3 mb-0"><?= number_format($summary['total']) ?></div>
						<small class="text-muted"><?= __d('queue', 'Total Jobs') ?></small>
					</div>
					<div class="col-md-2 col-sm-4 mb-3">
						<div class="h3 mb-0"><?= number_format($summary['avgPerHour'], 1) ?></div>
						<small class="text-muted"><?= __d('queue', 'Avg/Hour') ?></small>
					</div>
					<div class="col-md-2 col-sm-4 mb-3">
						<div class="h3 mb-0 text-success"><?= h($summary['peakHour']) ?></div>
						<small class="text-muted"><?= __d('queue', 'Peak Hour') ?> (<?= number_format($summary['peakCount']) ?>)</small>
					</div>
					<div class="col-md-2 col-sm-4 mb-3">
						<div class="h3 mb-0 text-secondary"><?= h($summary['quietestHour']) ?></div>
						<small class="text-muted"><?= __d('queue', 'Quietest') ?> (<?= number_format($summary['quietestCount']) ?>)</small>
					</div>
					<div class="col-md-2 col-sm-4 mb-3">
						<div class="h3 mb-0 text-primary"><?= h($summary['busiestDay']) ?></div>
						<small class="text-muted"><?= __d('queue', 'Busiest Day') ?></small>
					</div>
				</div>
			</div>
		</div>

		<!-- Heatmap Card -->
		<div class="card mb-4">
			<div class="card-header d-flex justify-content-between align-items-center">
				<span>
					<i class="fas fa-th me-2"></i><?= __d('queue', 'Activity Heatmap') ?>
					<small class="text-muted ms-2">(<?= $metric === 'created' ? __d('queue', 'Jobs Created') : __d('queue', 'Jobs Completed') ?>)</small>
				</span>
				<?php if ($jobType) { ?>
					<?= $this->Html->link(
						'<i class="fas fa-times me-1"></i>' . __d('queue', 'Clear Filter'),
						['action' => 'heatmap', '?' => ['metric' => $metric, 'days' => $days]],
						['class' => 'btn btn-sm btn-outline-secondary', 'escapeTitle' => false],
					) ?>
				<?php } ?>
			</div>
			<div class="card-body">
				<div class="heatmap-container">
					<!-- Hour labels -->
					<div class="heatmap-row heatmap-header">
						<div class="heatmap-day-label"></div>
						<?php for ($hour = 0; $hour < 24; $hour++) { ?>
							<div class="heatmap-hour-label"><?= sprintf('%02d', $hour) ?></div>
						<?php } ?>
					</div>

					<!-- Grid rows -->
					<?php foreach ($grid as $day => $hours) { ?>
						<div class="heatmap-row">
							<div class="heatmap-day-label"><?= $dayNames[$day] ?></div>
							<?php foreach ($hours as $hour => $count) { ?>
								<?php
								$intensity = $maxValue > 0 ? $count / $maxValue : 0;
								$bgColor = $this->Queue->heatmapColor($intensity);
								$textColor = $intensity > 0.5 ? '#fff' : '#333';
								?>
								<div class="heatmap-cell"
									 style="background-color: <?= $bgColor ?>; color: <?= $textColor ?>;"
									 data-day="<?= $dayNamesFull[$day] ?>"
									 data-hour="<?= sprintf('%02d:00-%02d:59', $hour, $hour) ?>"
									 data-count="<?= $count ?>"
									 data-bs-toggle="tooltip"
									 data-bs-placement="top"
									 data-bs-html="true"
									 title="<strong><?= $dayNamesFull[$day] ?> <?= sprintf('%02d:00', $hour) ?></strong><br><?= number_format($count) ?> <?= __d('queue', 'jobs') ?>">
									<?php if ($count > 0) { ?>
										<span class="heatmap-value"><?= $count > 999 ? round($count / 1000, 1) . 'k' : $count ?></span>
									<?php } ?>
								</div>
							<?php } ?>
						</div>
					<?php } ?>
				</div>

				<!-- Legend -->
				<div class="heatmap-legend mt-3">
					<small class="text-muted me-2"><?= __d('queue', 'Less') ?></small>
					<?php for ($i = 0; $i <= 4; $i++) { ?>
						<div class="heatmap-legend-cell" style="background-color: <?= $this->Queue->heatmapColor($i / 4) ?>;"></div>
					<?php } ?>
					<small class="text-muted ms-2"><?= __d('queue', 'More') ?></small>
				</div>
			</div>
		</div>
	</div>

	<!-- Sidebar -->
	<div class="col-lg-3">
		<!-- Filters Card -->
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-filter me-2"></i><?= __d('queue', 'Filters') ?>
			</div>
			<div class="card-body">
				<?= $this->Form->create(null, ['type' => 'get', 'url' => ['action' => 'heatmap']]) ?>

				<div class="mb-3">
					<label class="form-label small text-muted"><?= __d('queue', 'Metric') ?></label>
					<?= $this->Form->select(
						'metric',
						[
							'created' => __d('queue', 'Jobs Created'),
							'completed' => __d('queue', 'Jobs Completed'),
						],
						['value' => $metric, 'class' => 'form-select form-select-sm'],
					) ?>
				</div>

				<div class="mb-3">
					<label class="form-label small text-muted"><?= __d('queue', 'Time Range') ?></label>
					<?= $this->Form->select(
						'days',
						[
							7 => __d('queue', 'Last 7 days'),
							30 => __d('queue', 'Last 30 days'),
							90 => __d('queue', 'Last 90 days'),
							180 => __d('queue', 'Last 180 days'),
							365 => __d('queue', 'Last year'),
						],
						['value' => $days, 'class' => 'form-select form-select-sm'],
					) ?>
				</div>

				<div class="mb-3">
					<label class="form-label small text-muted"><?= __d('queue', 'Job Type') ?></label>
					<?= $this->Form->select(
						'job_type',
						['' => __d('queue', 'All Types')] + $jobTypes,
						[
							'value' => $jobType,
							'class' => 'form-select form-select-sm',
						],
					) ?>
				</div>

				<button type="submit" class="btn btn-primary btn-sm w-100">
					<i class="fas fa-sync me-1"></i><?= __d('queue', 'Apply') ?>
				</button>
				<?= $this->Form->end() ?>
			</div>
		</div>

		<!-- Navigation Card -->
		<div class="card">
			<div class="card-header">
				<i class="fas fa-chart-line me-2"></i><?= __d('queue', 'Statistics') ?>
			</div>
			<div class="list-group list-group-flush">
				<?= $this->Html->link(
					'<i class="fas fa-chart-line me-2"></i>' . __d('queue', 'Time Series'),
					['action' => 'stats'],
					['class' => 'list-group-item list-group-item-action', 'escapeTitle' => false],
				) ?>
				<?= $this->Html->link(
					'<i class="fas fa-th me-2"></i>' . __d('queue', 'Heatmap'),
					['action' => 'heatmap'],
					['class' => 'list-group-item list-group-item-action active', 'escapeTitle' => false],
				) ?>
				<?= $this->Html->link(
					'<i class="fas fa-arrow-left me-2"></i>' . __d('queue', 'Back to Dashboard'),
					['controller' => 'Queue', 'action' => 'index'],
					['class' => 'list-group-item list-group-item-action', 'escapeTitle' => false],
				) ?>
			</div>
		</div>
	</div>
</div>

<style>
.heatmap-container {
	overflow-x: auto;
}

.heatmap-row {
	display: flex;
	gap: 2px;
	margin-bottom: 2px;
}

.heatmap-header {
	margin-bottom: 4px;
}

.heatmap-day-label {
	width: 40px;
	min-width: 40px;
	font-size: 0.75rem;
	font-weight: 500;
	display: flex;
	align-items: center;
	color: #666;
}

.heatmap-hour-label {
	width: 28px;
	min-width: 28px;
	text-align: center;
	font-size: 0.65rem;
	color: #999;
}

.heatmap-cell {
	width: 28px;
	min-width: 28px;
	height: 28px;
	border-radius: 3px;
	display: flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
	transition: transform 0.1s ease, box-shadow 0.1s ease;
	position: relative;
}

.heatmap-cell:hover {
	transform: scale(1.15);
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
	z-index: 10;
}

.heatmap-value {
	font-size: 0.6rem;
	font-weight: 600;
}

.heatmap-legend {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 3px;
}

.heatmap-legend-cell {
	width: 16px;
	height: 16px;
	border-radius: 2px;
}

@media (max-width: 768px) {
	.heatmap-cell,
	.heatmap-hour-label {
		width: 20px;
		min-width: 20px;
		height: 20px;
	}

	.heatmap-value {
		font-size: 0.5rem;
	}
}
</style>

<?php $this->append('script'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	// Initialize Bootstrap tooltips
	var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
	tooltipTriggerList.forEach(function(tooltipTriggerEl) {
		new bootstrap.Tooltip(tooltipTriggerEl);
	});
});
</script>
<?php $this->end();
