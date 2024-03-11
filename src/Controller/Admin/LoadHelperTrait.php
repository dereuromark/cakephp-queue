<?php
declare(strict_types=1);

namespace Queue\Controller\Admin;

use Templating\View\Helper\IconHelper;
use Templating\View\Helper\IconSnippetHelper;

trait LoadHelperTrait {

	/**
	 * @return void
	 */
	protected function loadHelpers(): void {
		$helpers = [
			'Shim.Configure',
			'Tools.Format',
			'Tools.Text',
			'Tools.Time',
			class_exists(IconHelper::class) ? 'Templating.Icon' : 'Tools.Icon',
		];
		if (class_exists(IconSnippetHelper::class)) {
			$helpers[] = 'Templating.IconSnippet';
		}

		$this->viewBuilder()->addHelpers($helpers);
	}

}
