<?php
/**
 * Pagination element with Bootstrap 5 styling.
 * Fallback when Tools plugin is not available.
 *
 * @var \App\View\AppView $this
 */

if (!$this->Paginator->hasPage()) {
	return;
}
?>
<nav aria-label="Page navigation">
	<ul class="pagination justify-content-center">
		<?= $this->Paginator->first('«', ['class' => 'page-link']) ?>
		<?= $this->Paginator->prev('‹', ['class' => 'page-link']) ?>
		<?= $this->Paginator->numbers(['class' => 'page-link']) ?>
		<?= $this->Paginator->next('›', ['class' => 'page-link']) ?>
		<?= $this->Paginator->last('»', ['class' => 'page-link']) ?>
	</ul>
</nav>
<p class="text-center text-muted small">
	<?= $this->Paginator->counter(__d('queue', 'Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?>
</p>
