<?php
/**
 * Icon element with Font Awesome 6 fallback when Icon helper is not available.
 *
 * @var \App\View\AppView $this
 * @var string $name Icon name
 * @var array<string, mixed> $options Icon options
 * @var array<string, mixed> $attributes Icon attributes
 */

$options ??= [];
$attributes ??= [];

$fallbackMap = [
	'view' => ['icon' => 'eye', 'text' => 'View'],
	'edit' => ['icon' => 'edit', 'text' => 'Edit'],
	'delete' => ['icon' => 'trash', 'text' => 'Del'],
	'times' => ['icon' => 'times', 'text' => 'X'],
	'exclamation-triangle' => ['icon' => 'exclamation-triangle', 'text' => '(!)'],
	'cubes' => ['icon' => 'database', 'text' => 'Data'],
];

if ($this->helpers()->has('Icon')) {
	echo $this->Icon->render($name, $options, $attributes);
} else {
	$mapping = $fallbackMap[$name] ?? ['icon' => $name, 'text' => ucfirst($name)];
	$title = $attributes['title'] ?? $mapping['text'];
	$class = 'fas fa-' . $mapping['icon'];
	if (isset($attributes['class'])) {
		$class .= ' ' . $attributes['class'];
	}
	$text = $mapping['text'];
	echo '<i class="' . h($class) . '" title="' . h($title) . '"></i><span class="sr-only visually-hidden">' . h($text) . '</span>';
}
