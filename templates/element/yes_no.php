<?php
/**
 * Overwrite this element snippet locally to customize if needed.
 *
 * @var \App\View\AppView $this
 * @var bool $value
 */
?>
<?php
	if ($this->helpers()->has('IconSnippet')) {
		echo $this->IconSnippet->yesNo($value);
	} elseif ($this->helpers()->has('Format')) {
		echo $this->Format->yesNo($value);
	} else {
		echo $value ? '<span class="yes-no yes-no-yes">Yes</span>' : '<span class="yes-no yes-no-no">No</span>';
	}
?>
