<?php
/**
 * @var \Cake\View\View $this
 * @var array $params
 * @var string $message
 */

$class = 'alert alert-danger alert-dismissible fade show';
if (isset($params['class'])) {
	$class .= ' ' . $params['class'];
}
if (!isset($params['escape']) || $params['escape'] !== false) {
	$message = h($message);
}
?>
<div class="<?= h($class) ?>" role="alert">
	<i class="fas fa-exclamation-circle me-2"></i>
	<?= $message ?>
	<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
