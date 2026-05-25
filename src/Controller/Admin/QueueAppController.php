<?php
declare(strict_types=1);

namespace Queue\Controller\Admin;

use App\Controller\AppController;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Log\Log;
use Closure;
use Throwable;

/**
 * QueueAppController
 *
 * Base controller for Queue admin.
 *
 * Authentication: by default this extends AppController to inherit the host
 * app's auth and components. Set `Queue.standalone` to `true` for an
 * isolated admin that does not depend on the host app.
 *
 * Authorization: the admin UI can trigger jobs (via AddFromBackendInterface
 * tasks), reset/remove queued jobs, and terminate workers — operational
 * damage if exposed. The default policy is **deny**: the host application
 * MUST set `Queue.adminAccess` to a `Closure` that receives the current
 * request and returns literal `true` to grant access. Anything else
 * (unset, non-Closure, returns false, returns a truthy non-bool, or throws)
 * yields a 403.
 *
 * ```php
 * Configure::write('Queue.adminAccess', function (\Cake\Http\ServerRequest $request): bool {
 *     $identity = $request->getAttribute('identity');
 *     return $identity !== null && in_array('admin', (array)$identity->roles, true);
 * });
 * ```
 */
class QueueAppController extends AppController {

	use LoadHelperTrait;

	/**
	 * Current active connection name.
	 *
	 * @var string
	 */
	protected string $activeConnection = 'default';

	/**
	 * @return void
	 */
	public function initialize(): void {
		if (Configure::read('Queue.standalone')) {
			// Standalone mode: skip app's AppController, initialize independently
			Controller::initialize();
			$this->loadComponent('Flash');
		} else {
			// Default: inherit app's full controller setup
			parent::initialize();
		}

		$this->loadHelpers();

		// Layout configuration:
		// - null (default): Uses 'Queue.queue' isolated Bootstrap 5 layout
		// - false: Disables plugin layout, uses app's default layout
		// - string: Uses specified layout (e.g., 'Queue.queue' or custom)
		$layout = Configure::read('Queue.adminLayout');
		if ($layout !== false) {
			$this->viewBuilder()->setLayout($layout ?: 'Queue.queue');
		}

		// Multi-connection support
		$this->activeConnection = $this->resolveConnection();
		$this->set('queueConnections', $this->getConnections());
		$this->set('queueActiveConnection', $this->activeConnection);
	}

	/**
	 * Default-deny access gate. The plugin's admin UI can trigger jobs and
	 * mutate queue state, so accidental exposure (a logged-in but non-admin
	 * user, or a missing middleware in standalone mode) is treated as harmful
	 * by default.
	 *
	 * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event
     *
	 * @throws \Cake\Http\Exception\ForbiddenException When access is denied or unconfigured.
     *
	 * @return void
	 */
	public function beforeFilter(EventInterface $event): void {
		parent::beforeFilter($event);

		// Coexist with cakephp/authorization: the gate IS the authorization
		// decision for these controllers, so silence the policy check.
		if ($this->components()->has('Authorization') && method_exists($this->components()->get('Authorization'), 'skipAuthorization')) {
			$this->components()->get('Authorization')->skipAuthorization();
		}

		$gate = Configure::read('Queue.adminAccess');
		if (!($gate instanceof Closure)) {
			throw new ForbiddenException(__d(
				'queue',
				'Queue admin backend is not configured. Set Queue.adminAccess to a Closure that returns true for permitted callers.',
			));
		}

		try {
			$allowed = $gate($this->request) === true;
		} catch (ForbiddenException $e) {
			throw $e;
		} catch (Throwable $e) {
			Log::warning(sprintf('Queue.adminAccess threw %s: %s', $e::class, $e->getMessage()));

			throw new ForbiddenException(__d('queue', 'Queue admin access denied.'));
		}

		if (!$allowed) {
			throw new ForbiddenException(__d('queue', 'Queue admin access denied.'));
		}
	}

	/**
	 * Get configured connections.
	 *
	 * Returns null if multi-connection mode is not enabled.
	 *
	 * @return array<string>|null
	 */
	protected function getConnections(): ?array {
		$connections = Configure::read('Queue.connections');
		if (!$connections || !is_array($connections) || count($connections) < 2) {
			return null;
		}

		return $connections;
	}

	/**
	 * Resolve the active connection from request or config.
	 *
	 * Uses session to persist connection choice. Query parameter can override
	 * and update the session value.
	 *
	 * @throws \Cake\Http\Exception\NotFoundException If connection is not in whitelist
	 *
	 * @return string
	 */
	protected function resolveConnection(): string {
		$connections = Configure::read('Queue.connections');

		// Single connection mode (backwards compatible)
		if (!$connections || !is_array($connections) || count($connections) < 2) {
			return 'default';
		}

		$session = $this->request->getSession();

		// Check query string - allows switching via URL
		$requested = $this->request->getQuery('connection');

		if ($requested !== null) {
			// Validate against whitelist
			if (!in_array($requested, $connections, true)) {
				throw new NotFoundException(__d('queue', 'Invalid connection: {0}', $requested));
			}
			// Store in session for subsequent requests
			$session->write('Queue.connection', $requested);

			return $requested;
		}

		// Read from session
		$sessionConnection = $session->read('Queue.connection');
		if ($sessionConnection && in_array($sessionConnection, $connections, true)) {
			return $sessionConnection;
		}

		// Default to first connection
		return $connections[0];
	}

	/**
	 * Get the active connection name.
	 *
	 * @return string
	 */
	protected function getActiveConnection(): string {
		return $this->activeConnection;
	}

	/**
	 * Get the active connection object.
	 *
	 * @return \Cake\Database\Connection
	 */
	protected function getActiveConnectionObject(): Connection {
		/** @var \Cake\Database\Connection $connection */
		$connection = ConnectionManager::get($this->activeConnection);

		return $connection;
	}

}
