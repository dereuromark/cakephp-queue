<?php

use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $outer_routes) {
	$outer_routes->prefix('Admin', function (RouteBuilder $routes) {
		$routes->plugin('Queue', function (RouteBuilder $routes) {
			$routes->connect('/', ['controller' => 'Queue', 'action' => 'index']);

			$routes->fallbacks();
		});
	});

	$outer_routes->plugin('Queue', ['path' => '/queue'], function (RouteBuilder $routes) {
		$routes->connect('/:controller');
	});
};

