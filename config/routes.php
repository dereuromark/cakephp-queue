<?php
/**
 * @var RouteBuilder $routes
 */

use Cake\Routing\RouteBuilder;

$routes->prefix('Admin', function (RouteBuilder $routes) {
	$routes->plugin('Queue', function (RouteBuilder $routes) {
		$routes->connect('/', ['controller' => 'Queue', 'action' => 'index']);

		$routes->fallbacks();
	});
});

$routes->plugin('Queue', ['path' => '/queue'], function (RouteBuilder $routes) {
	$routes->connect('/:controller');
});
