<?php
/**
 * Standalone pagination element with Bootstrap 5 styling.
 *
 * Sets explicit templates to avoid style leakage from app templates.
 *
 * @var \Cake\View\View $this
 */

if (!$this->Paginator->hasPage()) {
	return;
}

// Set Bootstrap 5 templates explicitly to avoid app template leakage
$this->Paginator->setTemplates([
	'nextActive' => '<li class="page-item"><a class="page-link" rel="next" href="{{url}}">{{text}}</a></li>',
	'nextDisabled' => '<li class="page-item disabled"><span class="page-link">{{text}}</span></li>',
	'prevActive' => '<li class="page-item"><a class="page-link" rel="prev" href="{{url}}">{{text}}</a></li>',
	'prevDisabled' => '<li class="page-item disabled"><span class="page-link">{{text}}</span></li>',
	'first' => '<li class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></li>',
	'last' => '<li class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></li>',
	'number' => '<li class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></li>',
	'current' => '<li class="page-item active"><span class="page-link">{{text}}</span></li>',
]);
?>
<nav class="mt-3" aria-label="<?= __d('queue', 'Page navigation') ?>">
	<ul class="pagination justify-content-center mb-2">
		<?= $this->Paginator->first('<i class="fas fa-angle-double-left"></i>', ['escape' => false]) ?>
		<?= $this->Paginator->prev('<i class="fas fa-angle-left"></i>', ['escape' => false]) ?>
		<?= $this->Paginator->numbers() ?>
		<?= $this->Paginator->next('<i class="fas fa-angle-right"></i>', ['escape' => false]) ?>
		<?= $this->Paginator->last('<i class="fas fa-angle-double-right"></i>', ['escape' => false]) ?>
	</ul>
	<p class="text-center text-muted small mb-0">
		<?= $this->Paginator->counter(__d('queue', 'Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?>
	</p>
</nav>
