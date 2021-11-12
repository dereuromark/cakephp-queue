<?php

namespace Queue\Controller\Admin;

use Cake\Core\Configure;

trait LoadHelperTrait {

	/**
	 * @return void
	 */
	protected function loadHelpers(): void {
		$helpers = [
			'Time' => ['config' => ['class' => 'Tools.Time', 'engine' => 'Tools\Utility\FrozenTime']],
			'Tools.Format',
			'Tools.Text',
			'Shim.Configure',
		];

		$version = Configure::version();
		if (version_compare($version, '4.3.0') >= 0) {
			$this->viewBuilder()->addHelpers($helpers);
		} else {
			$this->viewBuilder()->setHelpers($helpers);
		}
	}

}
