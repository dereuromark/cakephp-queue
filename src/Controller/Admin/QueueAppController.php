<?php
declare(strict_types=1);

namespace Queue\Controller\Admin;

use App\Controller\AppController;
use Cake\Controller\Controller;
use Cake\Core\Configure;

/**
 * QueueAppController
 *
 * Base controller for Queue admin.
 *
 * By default, extends AppController to inherit app authentication, components, and configuration.
 * Set `Queue.standalone` to `true` for an isolated admin that doesn't depend on the host app.
 */
class QueueAppController extends AppController {

	use LoadHelperTrait;

	/**
	 * @return void
	 */
	public function initialize(): void {
		if (Configure::read('Queue.standalone')) {
			// Standalone mode: skip app's AppController, initialize independently
			Controller::initialize();
			$this->loadComponent('Flash');
		} else {
			// Default: inherit app's full controller setup
			parent::initialize();
		}

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
