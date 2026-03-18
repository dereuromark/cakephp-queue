<?php
/**
 * Overwrite this element snippet locally to customize if needed.
 *
 * @var \App\View\AppView $this
 * @var string $value
 * @var bool $ok
 * @var bool $warning Optional warning state (orange/yellow)
 */
$warning = $warning ?? false;
?>
<?php
	if (!isset($warning) && $this->helpers()->has('Templating')) {
		echo $this->Templating->ok($value, $ok);
	} elseif (!isset($warning) && $this->helpers()->has('Format')) {
		echo $this->Format->ok($value, $ok);
	} else {
		if ($ok && !$warning) {
			echo '<span class="yes-no yes-no-yes"><i class="fas fa-check me-1"></i>' . h($value) . '</span>';
		} elseif ($warning) {
			echo '<span class="yes-no yes-no-warn text-warning"><i class="fas fa-exclamation-circle me-1"></i>' . h($value) . '</span>';
		} else {
			echo '<span class="yes-no yes-no-no"><i class="fas fa-exclamation-triangle me-1"></i>' . h($value) . '</span>';
		}
	}
?>
