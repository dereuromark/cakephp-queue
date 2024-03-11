<?php
/**
 * Overwrite this element snippet locally to customize if needed.
 *
 * @var \App\View\AppView $this
 * @var string $value
 * @var bool $ok
 */
?>
<?php
	if ($this->helpers()->has('Templating')) {
		echo $this->Templating->ok($value, $ok);
	} elseif ($this->helpers()->has('Format')) {
		echo $this->Format->ok($value, $ok);
	} else {
		echo $ok ? '<span class="yes-no yes-no-yes">' . h($value) . '</span>' : '<span class="yes-no yes-no-no">' . h($value) . '</span>';
	}
?>
