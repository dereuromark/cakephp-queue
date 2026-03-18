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

		// Layout configuration:
		// - null (default): Uses 'Queue.queue' isolated Bootstrap 5 layout
		// - false: Disables plugin layout, uses app's default layout
		// - string: Uses specified layout (e.g., 'Queue.queue' or custom)
		$layout = Configure::read('Queue.adminLayout');
		if ($layout !== false) {
			$this->viewBuilder()->setLayout($layout ?: 'Queue.queue');
		}
	}

}
