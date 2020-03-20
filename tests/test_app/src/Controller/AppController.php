<?php

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;

class AppController extends Controller {

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('Flash');
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function beforeRender(EventInterface $event) {
		parent::beforeRender($event);

		$this->viewBuilder()->setHelpers(['Tools.Time', 'Tools.Format']);
	}

}
