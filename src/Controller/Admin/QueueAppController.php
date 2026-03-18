<?php
declare(strict_types=1);

namespace Queue\Controller\Admin;

use Cake\Controller\Controller;
use Cake\Core\Configure;

/**
 * QueueAppController
 *
 * Isolated base controller for Queue admin that doesn't depend on host app's AppController.
 * This ensures the admin dashboard can function independently with its own layout and styling.
 */
class QueueAppController extends Controller {

	use LoadHelperTrait;

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('Flash');

		$this->loadHelpers();

		$layout = Configure::read('Queue.adminLayout');
		if ($layout) {
			$this->viewBuilder()->setLayout($layout);
		}
	}

}
