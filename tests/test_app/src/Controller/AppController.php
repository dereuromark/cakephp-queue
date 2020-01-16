<?php

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;

class AppController extends Controller {

	/**
	 * @return void
	 */
	public function initialize() {
		parent::initialize();

		$this->loadComponent('Flash');
	}

	/**
	 * @param \Cake\Event\Event $event
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function beforeRender(Event $event) {
		parent::beforeRender($event);

		$this->viewBuilder()->setHelpers(['Tools.Time', 'Tools.Format']);
	}

}
