<?php

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::prefix('Admin', function (RouteBuilder $routes) {
	$routes->plugin('Queue', function (RouteBuilder $routes) {
		$routes->connect('/', ['controller' => 'Queue', 'action' => 'index']);

		$routes->fallbacks();
	});
});

Router::plugin('Queue', ['path' => '/queue'], function (RouteBuilder $routes) {
	$routes->connect('/:controller');
});
