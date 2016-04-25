<?php
use Cake\Routing\Router;

Router::prefix('admin', function ($routes) {
	$routes->plugin('Queue', function ($routes) {
		$routes->connect('/', ['controller' => 'Queue', 'action' => 'index'], ['routeClass' => 'DashedRoute']);

		$routes->connect('/:controller', ['action' => 'index'], ['routeClass' => 'DashedRoute']);
		$routes->connect('/:controller/:action/*', [], ['routeClass' => 'DashedRoute']);
	});
});

Router::plugin('Queue', ['path' => '/queue'], function ($routes) {
	$routes->connect('/:controller');
});
