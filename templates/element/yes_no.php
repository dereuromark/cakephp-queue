<?php
/**
 * Overwrite this element snippet locally to customize if needed.
 *
 * @var \App\View\AppView $this
 * @var bool $value
 */
?>
<?php
	if (isset($this->IconSnippet)) {
		echo $this->IconSnippet->yesNo($value);
	} elseif (isset($this->Format)) {
		echo $this->Format->yesNo($value);
	} else {
		echo $value ? '<span class="yes-no yes-no-yes">Yes</span>' : '<span class="yes-no yes-no-no">No</span>';
	}
?>
