<?php

namespace Queue\Queue;

use Cake\Core\Configure;

class Config {

	/**
	 * @return int
	 */
	public static function defaultworkertimeout() {
		return Configure::read('Queue.defaultworkertimeout', 1800); // 1h
	}

	/**
	 * @return int
	 */
	public static function workermaxruntime() {
		return Configure::read('Queue.workermaxruntime', 120);
	}

	/**
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

}
