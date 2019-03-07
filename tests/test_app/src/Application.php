<?php

namespace App;

use Cake\Http\BaseApplication;
use Cake\Routing\Middleware\RoutingMiddleware;

class Application extends BaseApplication {

	/**
	 * @param \Cake\Http\MiddlewareQueue $middleware The middleware queue to set in your App Class
	 * @return \Cake\Http\MiddlewareQueue
	 */
	public function middleware($middleware) {
		$middleware->add(new RoutingMiddleware($this));

		return $middleware;
	}

}
