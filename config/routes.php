<?php
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

Router::prefix('admin', function (RouteBuilder $routes) {
	$routes->plugin('Queue', function (RouteBuilder $routes) {
		$routes->connect('/', ['controller' => 'Queue', 'action' => 'index'], ['routeClass' => DashedRoute::class]);

		$routes->fallbacks(DashedRoute::class);
	});
});

Router::plugin('Queue', ['path' => '/queue'], function (RouteBuilder $routes) {
	$routes->connect('/:controller');
});
