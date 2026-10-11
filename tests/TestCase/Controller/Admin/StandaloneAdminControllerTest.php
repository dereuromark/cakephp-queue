<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Controller\Admin;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Http\Exception\ForbiddenException;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class StandaloneAdminControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
     * @var list<string>
     */
	protected array $fixtures = ['plugin.Queue.QueuedJobs', 'plugin.Queue.QueueProcesses'];

	/**
     * @return void
     */
	protected function setUp(): void {
		parent::setUp();
		$this->loadPlugins(['Queue']);
	}

	/**
     * @return void
     */
	protected function tearDown(): void {
		Configure::delete('Queue.standalone');
		Configure::delete('Test');
		parent::tearDown();
	}

	public function testHostBeforeFilterRedirectStopsAction(): void {
		Configure::write('Queue.standalone', false);
		Configure::write('Test.hostRedirect', true);
		Configure::write('Queue.adminAccess', function (): bool {
			$this->fail('The plugin gate must not run after a host redirect.');
		});

		try {
			$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'index']);
			$this->assertRedirect('/login');
		} finally {
			Configure::delete('Test.hostRedirect');
		}
	}

	public function testStandaloneMarksRequestAuthorizationChecked(): void {
		$service = new class {
			public bool $skipped = false;

			public function skipAuthorization(): void {
				$this->skipped = true;
			}
		};
		Configure::write('Queue.standalone', true);
		Configure::write('Queue.adminAccess', fn (): bool => true);
		$listener = function (EventInterface $event) use ($service): void {
			$controller = $event->getSubject();
			$controller->setRequest($controller->getRequest()->withAttribute('authorization', $service));
		};
		EventManager::instance()->on('Controller.initialize', $listener);

		try {
			$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'index']);
		} finally {
			EventManager::instance()->off('Controller.initialize', $listener);
		}

		$this->assertResponseOk();
		$this->assertTrue($service->skipped);
	}

	public function testDefaultCallsHostCallbacks(): void {
		Configure::write('Queue.standalone', false);
		Configure::write('Queue.adminAccess', fn (): bool => true);
		Configure::delete('Test.hostInitialize');
		Configure::delete('Test.hostBeforeFilter');
		Configure::delete('Test.hostBeforeRender');
		Configure::delete('Test.hostAfterFilter');
		Configure::delete('Test.hostBeforeRedirect');

		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'index']);

		$this->assertResponseOk();
		$this->assertTrue(Configure::read('Test.hostInitialize'));
		$this->assertTrue(Configure::read('Test.hostBeforeFilter'));
		$this->assertTrue(Configure::read('Test.hostBeforeRender'));
		$this->assertTrue(Configure::read('Test.hostAfterFilter'));
		$this->_controller->redirect('/');
		$this->assertTrue(Configure::read('Test.hostBeforeRedirect'));
	}

	public function testStandaloneSkipsHostCallbacks(): void {
		Configure::write('Queue.standalone', true);
		Configure::write('Queue.adminAccess', fn (): bool => true);
		Configure::delete('Test.hostInitialize');
		Configure::delete('Test.hostBeforeFilter');
		Configure::delete('Test.hostBeforeRender');
		Configure::delete('Test.hostAfterFilter');
		Configure::delete('Test.hostBeforeRedirect');

		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'index']);

		$this->assertResponseOk();
		$this->assertNull(Configure::read('Test.hostInitialize'));
		$this->assertNull(Configure::read('Test.hostBeforeFilter'));
		$this->assertNull(Configure::read('Test.hostBeforeRender'));
		$this->assertNull(Configure::read('Test.hostAfterFilter'));
		$this->_controller->redirect('/');
		$this->assertNull(Configure::read('Test.hostBeforeRedirect'));
	}

	public function testStandaloneWithoutGateRejects(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::write('Queue.standalone', true);
		Configure::delete('Queue.adminAccess');

		$this->expectException(ForbiddenException::class);
		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'index']);
	}

	public function testStandaloneDeniedGateRejects(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::write('Queue.standalone', true);
		Configure::write('Queue.adminAccess', fn (): bool => false);

		$this->expectException(ForbiddenException::class);
		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'index']);
	}

}
