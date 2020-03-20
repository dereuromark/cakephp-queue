<?php

namespace TestApp;

use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;

class Application extends BaseApplication {

	/**
	 * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to set in your App Class
	 * @return \Cake\Http\MiddlewareQueue
	 */
	public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue {
		$middlewareQueue->add(new RoutingMiddleware($this));

		return $middlewareQueue;
	}

	/**
	 * @return void
	 */
	public function bootstrap(): void {
		parent::bootstrap();

		$this->addPlugin('Tools');
		$this->addPlugin('Foo', ['path' => ROOT . DS . 'tests' . DS . 'test_app' . DS . 'plugins' . DS . 'Foo' . DS]);
	}

}
