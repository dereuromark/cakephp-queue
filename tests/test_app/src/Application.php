<?php
declare(strict_types=1);

namespace TestApp;

use Cake\Core\ContainerInterface;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;
use TestApp\Services\TestService;

class Application extends BaseApplication {

	/**
	 * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to set in your App Class
	 *
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
		$this->addPlugin('Foo', ['path' => PLUGIN_ROOT . DS . 'tests' . DS . 'test_app' . DS . 'plugins' . DS . 'Foo' . DS]);
	}

	/**
	 * @param \Cake\Core\ContainerInterface $container
	 *
	 * @return void
	 */
	public function services(ContainerInterface $container): void {
		$container->add(TestService::class);
	}

}
