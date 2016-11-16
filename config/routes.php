<?php
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

Router::prefix('admin', function ($routes) {
	$routes->plugin('Queue', function ($routes) {
		$routes->connect('/', ['controller' => 'Queue', 'action' => 'index'], ['routeClass' => DashedRoute::class]);

		$routes->fallbacks(DashedRoute::class);
	});
});

Router::plugin('Queue', ['path' => '/queue'], function ($routes) {
	$routes->connect('/:controller');
});
