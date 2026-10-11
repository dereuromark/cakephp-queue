<?php

declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Psr\Http\Message\UriInterface;

class AppController extends Controller {

	public function initialize(): void {
		parent::initialize();
		Configure::write('Test.hostInitialize', true);

		$this->loadComponent('Flash');
	}

	/**
	 * @param \Cake\Event\EventInterface $event Event
     *
	 * @return mixed
	 */
	public function beforeFilter(EventInterface $event) {
		parent::beforeFilter($event);
		Configure::write('Test.hostBeforeFilter', true);
		if (Configure::read('Test.hostRedirectViaEvent')) {
			$event->setResult($this->redirect('/login'));

			return null;
		}
		if (Configure::read('Test.hostRedirect')) {
			return $this->redirect('/login');
		}

		return null;
	}

	/**
     * @param \Cake\Event\EventInterface $event Event
     *
     * @return void
     */
	public function beforeRender(EventInterface $event) {
		Configure::write('Test.hostBeforeRender', true);

		parent::beforeRender($event);
	}

	/**
     * @param \Cake\Event\EventInterface $event Event
     *
     * @return void
     */
	public function afterFilter(EventInterface $event) {
		Configure::write('Test.hostAfterFilter', true);

		parent::afterFilter($event);
	}

	/**
     * @param \Cake\Event\EventInterface $event Event
     * @param \Psr\Http\Message\UriInterface|array|string $url URL
     * @param \Cake\Http\Response $response Response
     *
     * @return void
     */
	public function beforeRedirect(EventInterface $event, UriInterface|array|string $url, Response $response) {
		Configure::write('Test.hostBeforeRedirect', true);

		parent::beforeRedirect($event, $url, $response);
	}

}
