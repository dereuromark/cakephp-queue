<?php
/**
 * Icon element with text fallback when Icon helper is not available.
 *
 * @var \App\View\AppView $this
 * @var string $name Icon name
 * @var array<string, mixed> $options Icon options
 * @var array<string, mixed> $attributes Icon attributes
 */

$options ??= [];
$attributes ??= [];

$fallbackMap = [
	'view' => 'View',
	'edit' => 'Edit',
	'delete' => 'Del',
	'times' => 'X',
	'exclamation-triangle' => '(!)',
	'cubes' => 'Data',
];

if ($this->helpers()->has('Icon')) {
	echo $this->Icon->render($name, $options, $attributes);
} else {
	$title = $attributes['title'] ?? $fallbackMap[$name] ?? ucfirst($name);
	echo '<span title="' . h($title) . '">[' . h($fallbackMap[$name] ?? ucfirst($name)) . ']</span>';
}
