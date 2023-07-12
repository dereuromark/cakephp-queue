<?php
declare(strict_types=1);

namespace Queue\Controller\Admin;

trait LoadHelperTrait {

	/**
	 * @return void
	 */
	protected function loadHelpers(): void {
		$helpers = [
			'Tools.Time',
			'Tools.Format',
			'Tools.Icon',
			'Tools.Text',
			'Shim.Configure',
		];

		$this->viewBuilder()->addHelpers($helpers);
	}

}
