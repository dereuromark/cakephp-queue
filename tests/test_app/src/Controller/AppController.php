<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;

class AppController extends Controller {

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('Flash');
	}

}
