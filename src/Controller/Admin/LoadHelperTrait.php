<?php
declare(strict_types=1);

namespace Queue\Controller\Admin;

use Templating\View\Helper\IconHelper;

trait LoadHelperTrait {

	/**
	 * @return void
	 */
	protected function loadHelpers(): void {
		$helpers = [
			'Tools.Time',
			'Tools.Format',
			class_exists(IconHelper::class) ? 'Templating.Icon' : 'Tools.Icon',
			'Tools.Text',
			'Shim.Configure',
		];

		$this->viewBuilder()->addHelpers($helpers);
	}

}
