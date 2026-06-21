<?php
/**
 * Overwrite this element snippet locally to customize if needed.
 *
 * @var \App\View\AppView $this
 * @var bool $value
 */
?>
<?php
	if ($this->helpers()->has('Templating')) {
		echo $this->Templating->yesNo($value);
	} elseif ($this->helpers()->has('IconSnippet')) {
		echo $this->IconSnippet->yesNo($value);
	} elseif ($this->helpers()->has('Format')) {
		echo $this->Format->yesNo($value);
	} else {
		if ($value) {
			echo '<span class="yes-no yes-no-yes"><i class="fas fa-check me-1"></i>Yes</span>';
		} else {
			echo '<span class="yes-no yes-no-no"><i class="fas fa-times me-1"></i>No</span>';
		}
	}
?>
