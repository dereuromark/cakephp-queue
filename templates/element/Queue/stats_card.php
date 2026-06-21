<?php
/**
 * Statistics Card Element
 *
 * @var \Cake\View\View $this
 * @var string $title Card title/label
 * @var int|string $count The statistic value
 * @var string $icon Font Awesome icon name (without 'fa-' prefix)
 * @var string $color Color variant (primary, success, warning, danger, info, secondary)
 * @var string|null $link Optional link URL
 */

$color ??= 'primary';
$link ??= null;

$colorClasses = [
	'primary' => ['bg' => 'bg-primary bg-opacity-10', 'text' => 'text-primary'],
	'success' => ['bg' => 'bg-success bg-opacity-10', 'text' => 'text-success'],
	'warning' => ['bg' => 'bg-warning bg-opacity-10', 'text' => 'text-warning'],
	'danger' => ['bg' => 'bg-danger bg-opacity-10', 'text' => 'text-danger'],
	'info' => ['bg' => 'bg-info bg-opacity-10', 'text' => 'text-info'],
	'secondary' => ['bg' => 'bg-secondary bg-opacity-10', 'text' => 'text-secondary'],
];

$classes = $colorClasses[$color] ?? $colorClasses['primary'];
?>
<div class="card stats-card h-100">
	<?php if ($link): ?>
	<a href="<?= h($link) ?>" class="text-decoration-none">
	<?php endif; ?>
		<div class="card-body">
			<div class="d-flex align-items-center">
				<div class="stats-icon <?= $classes['bg'] ?> <?= $classes['text'] ?>">
					<i class="fas fa-<?= h($icon) ?>"></i>
				</div>
				<div class="ms-3">
					<div class="stats-value <?= $classes['text'] ?>"><?= h($count) ?></div>
					<div class="stats-label"><?= h($title) ?></div>
				</div>
			</div>
		</div>
	<?php if ($link): ?>
	</a>
	<?php endif; ?>
</div>
