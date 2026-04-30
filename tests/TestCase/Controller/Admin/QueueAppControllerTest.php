<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Controller\Admin;

use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;
use Cake\TestSuite\IntegrationTestTrait;
use RuntimeException;
use Shim\TestSuite\TestCase;

/**
 * @uses \Queue\Controller\Admin\QueueAppController
 */
class QueueAppControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.Queue.QueuedJobs',
		'plugin.Queue.QueueProcesses',
	];

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->loadPlugins(['Queue']);
	}

	/**
	 * Without a configured Queue.adminAccess gate, the backend must fail
	 * closed (403). The test bootstrap installs a permissive default; we
	 * delete it for this test only.
	 *
	 * @return void
	 */
	public function testAdminAccessUnconfiguredYields403(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::delete('Queue.adminAccess');

		$this->expectException(ForbiddenException::class);
		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'index']);
	}

	/**
	 * @return void
	 */
	public function testAdminAccessNonClosureYields403(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::write('Queue.adminAccess', 'not a closure');

		$this->expectException(ForbiddenException::class);
		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'index']);
	}

	/**
	 * @return void
	 */
	public function testAdminAccessClosureFalseYields403(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::write('Queue.adminAccess', fn () => false);

		$this->expectException(ForbiddenException::class);
		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'index']);
	}

	/**
	 * @return void
	 */
	public function testAdminAccessRequiresStrictTrue(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::write('Queue.adminAccess', fn () => 1);

		$this->expectException(ForbiddenException::class);
		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'index']);
	}

	/**
	 * @return void
	 */
	public function testAdminAccessThrowingYields403(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::write('Queue.adminAccess', function (): bool {
			throw new RuntimeException('oops');
		});

		$this->expectException(ForbiddenException::class);
		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'index']);
	}

	/**
	 * @return void
	 */
	public function testAdminAccessExplicitForbiddenIsRespected(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::write('Queue.adminAccess', function (): bool {
			throw new ForbiddenException('custom denial reason');
		});

		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('custom denial reason');
		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'index']);
	}

	/**
	 * @return void
	 */
	public function testAdminAccessReceivesRequest(): void {
		$received = null;
		Configure::write('Queue.adminAccess', function ($request) use (&$received): bool {
			$received = $request;

			return true;
		});

		$this->get(['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'index']);

		$this->assertResponseOk();
		$this->assertNotNull($received);
		$this->assertStringContainsString('queue', $received->getPath());
	}

}
