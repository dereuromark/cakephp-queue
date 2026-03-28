<?php
/**
 * Standalone flash message element.
 *
 * Renders all flash messages directly from session to avoid
 * style leakage from app templates in standalone layouts.
 *
 * @var \Cake\View\View $this
 */

$flashMessages = $this->getRequest()->getSession()->consume('Flash.flash');
if (!$flashMessages) {
	return;
}

foreach ($flashMessages as $flash) {
	$element = $flash['element'] ?? 'flash/default';
	$alertClass = match ($element) {
		'flash/success' => 'alert-success',
		'flash/error' => 'alert-danger',
		'flash/warning' => 'alert-warning',
		default => 'alert-info',
	};
	$icon = match ($element) {
		'flash/success' => 'fa-check-circle',
		'flash/error' => 'fa-exclamation-circle',
		'flash/warning' => 'fa-exclamation-triangle',
		default => 'fa-info-circle',
	};
	?>
	<div class="alert <?= $alertClass ?> alert-dismissible fade show" role="alert">
		<i class="fas <?= $icon ?> me-2"></i>
		<?= h($flash['message']) ?>
		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
	</div>
	<?php
}
