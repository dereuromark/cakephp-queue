<?php
declare(strict_types=1);

namespace Queue\Queue;

use Cake\Core\Configure;
use InvalidArgumentException;

class Config {

	/**
	 * Timeout in seconds, after which the Task is reassigned to a new worker
	 * if not finished successfully.
	 * This should be high enough that it cannot still be running on a zombie worker (>> 2x) and cannot be zero.
	 *
	 * @return int
	 */
	public static function defaultworkertimeout(): int {
		// Check for new config name first, fall back to old name for backward compatibility
		$timeout = Configure::read('Queue.defaultRequeueTimeout');
		if ($timeout === null) {
			$timeout = Configure::read('Queue.defaultworkertimeout');
			if ($timeout !== null) {
				trigger_error(
					'Config key "Queue.defaultworkertimeout" is deprecated. Use "Queue.defaultRequeueTimeout" instead.',
					E_USER_DEPRECATED,
				);
			}
		}
		$timeout = $timeout ?? 600; // 10min default

		if ($timeout <= 0) {
			throw new InvalidArgumentException('Queue.defaultRequeueTimeout (or deprecated defaultworkertimeout) is less or equal than zero. Indefinite running of jobs is not supported.');
		}

		return $timeout;
	}

	/**
	 * Seconds of running time after which the worker will terminate.
	 * Note: 0 = unlimited is allowed but not recommended. Use a non-zero value for better control.
	 *
	 * @return int
	 */
	public static function workermaxruntime(): int {
		// Check for new config name first, fall back to old name for backward compatibility
		$runtime = Configure::read('Queue.workerLifetime');
		if ($runtime === null) {
			$runtime = Configure::read('Queue.workermaxruntime');
			if ($runtime !== null) {
				trigger_error(
					'Config key "Queue.workermaxruntime" is deprecated. Use "Queue.workerLifetime" instead.',
					E_USER_DEPRECATED,
				);
			}
		}

		return $runtime ?? 120;
	}

	/**
	 * Minimum number of seconds before a cleanup run will remove a completed task (set to 0 to disable)
	 *
	 * @return int
	 */
	public static function cleanuptimeout(): int {
		return Configure::read('Queue.cleanuptimeout', 2592000); // 30 days
	}

	/**
	 * @return int
	 */
	public static function sleeptime(): int {
		return Configure::read('Queue.sleeptime', 10);
	}

	/**
	 * @return int
	 */
	public static function gcprob(): int {
		return Configure::read('Queue.gcprob', 10);
	}

	/**
	 * @return int
	 */
	public static function defaultworkerretries(): int {
		// Check for new config name first, fall back to old name for backward compatibility
		$retries = Configure::read('Queue.defaultJobRetries');
		if ($retries === null) {
			$retries = Configure::read('Queue.defaultworkerretries');
			if ($retries !== null) {
				trigger_error(
					'Config key "Queue.defaultworkerretries" is deprecated. Use "Queue.defaultJobRetries" instead.',
					E_USER_DEPRECATED,
				);
			}
		}

		return $retries ?? 1;
	}

	/**
	 * @return int
	 */
	public static function maxworkers(): int {
		return Configure::read('Queue.maxworkers', 1);
	}

	/**
	 * @return array<string>
	 */
	public static function ignoredTasks(): array {
		$a = Configure::read('Queue.ignoredTasks', []);
		if (!is_array($a)) {
			throw new InvalidArgumentException('Queue.ignoredTasks is not an array');
		}

		return $a;
	}

	/**
	 * @param array<string> $tasks
	 *
	 * @throws \RuntimeException
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function taskConfig(array $tasks): array {
		$config = [];
		$defaultTimeout = static::defaultworkertimeout();

		foreach ($tasks as $task => $className) {
			[$pluginName, $taskName] = pluginSplit($task);

			/** @var \Queue\Queue\Task $taskObject */
			$taskObject = new $className();

			$taskTimeout = $taskObject->timeout ?? $defaultTimeout;

			// Warn if task timeout is greater than the requeue timeout, which can cause premature requeuing
			if ($taskTimeout > $defaultTimeout) {
				trigger_error(
					sprintf(
						'Task "%s" has timeout (%d seconds) larger than defaultRequeueTimeout (%d seconds). '
						. 'The job will be requeued after %d seconds even though the task expects to run for %d seconds. '
						. 'This can cause duplicate execution. Consider increasing defaultRequeueTimeout or decreasing the task timeout.',
						$task,
						$taskTimeout,
						$defaultTimeout,
						$defaultTimeout,
						$taskTimeout,
					),
					E_USER_WARNING,
				);
			}

			$config[$task]['class'] = $className;
			$config[$task]['name'] = $taskName;
			$config[$task]['plugin'] = $pluginName;
			$config[$task]['timeout'] = $taskTimeout;
			$config[$task]['retries'] = $taskObject->retries ?? static::defaultworkerretries();
			$config[$task]['rate'] = $taskObject->rate;
			$config[$task]['costs'] = $taskObject->costs;
			$config[$task]['unique'] = $taskObject->unique;

			unset($taskObject);
		}

		return $config;
	}

	/**
	 * @phpstan-param class-string<\Queue\Queue\Task>|string $class
	 *
	 * @param string $class
	 *
	 * @return string
	 */
	public static function taskName(string $class): string {
		preg_match('#^(.+?)\\\\Queue\\\\Task\\\\(.+?)Task$#', $class, $matches);
		if (!$matches) {
			throw new InvalidArgumentException('Invalid class name: ' . $class);
		}

		$namespace = str_replace('\\', '/', $matches[1]);
		if ($namespace === Configure::read('App.namespace')) {
			return $matches[2];
		}

		return $namespace . '.' . $matches[2];
	}

}
