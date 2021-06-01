<?php

namespace Queue\Queue;

use Cake\Core\Configure;

class Config {

	/**
	 * Timeout in seconds, after which the Task is reassigned to a new worker
	 * if not finished successfully.
	 * This should be high enough that it cannot still be running on a zombie worker (>> 2x).
	 *
	 * @return int
	 */
	public static function defaultworkertimeout() {
		return Configure::read('Queue.defaultworkertimeout', 600); // 10min
	}

	/**
	 * Seconds of running time after which the worker will terminate (0 = unlimited)
	 *
	 * @return int
	 */
	public static function workermaxruntime() {
		return Configure::read('Queue.workermaxruntime', 120);
	}

	/**
	 * Minimum number of seconds before a cleanup run will remove a completed task (set to 0 to disable)
	 *
	 * @return int
	 */
	public static function cleanuptimeout() {
		return Configure::read('Queue.cleanuptimeout', 2592000); // 30 days
	}

	/**
	 * @return int
	 */
	public static function sleeptime() {
		return Configure::read('Queue.sleeptime', 10);
	}

	/**
	 * @return int
	 */
	public static function gcprob() {
		return Configure::read('Queue.gcprob', 10);
	}

	/**
	 * @return int
	 */
	public static function defaultworkerretries() {
		return Configure::read('Queue.defaultworkerretries', 1);
	}

	/**
	 * @return int
	 */
	public static function maxworkers() {
		return Configure::read('Queue.maxworkers', 1);
	}

	/**
	 * @param string[] $tasks
	 *
	 * @throws \RuntimeException
	 * @return array
	 */
	public static function taskConfig(array $tasks): array {
		$config = [];

		foreach ($tasks as $task => $className) {
			[$pluginName, $taskName] = pluginSplit($task);

			/** @var \Queue\Queue\Task $taskObject */
			$taskObject = new $className();

			$config[$task]['class'] = $className;
			$config[$task]['name'] = $taskName;
			$config[$task]['plugin'] = $pluginName;
			$config[$task]['timeout'] = $taskObject->timeout ?? static::defaultworkertimeout();
			$config[$task]['retries'] = $taskObject->retries ?? static::defaultworkerretries();
			$config[$task]['rate'] = $taskObject->rate;
			$config[$task]['costs'] = $taskObject->costs;
			$config[$task]['unique'] = $taskObject->unique;

			unset($taskObject);
		}

		return $config;
	}

}
