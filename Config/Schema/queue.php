<?php

/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */
class QueueSchema extends CakeSchema {

/**
 * Before handler
 *
 * @param array $event event
 * @return bool always true
 */
	public function before($event = []) {
		return true;
	}

/**
 * After handler
 *
 * @param array $event event
 * @return void
 */
	public function after($event = []) {
	}

	public $queuedTasks = [
		'id' => [
			'type' => 'integer',
			'null' => false,
			'default' => null,
			'length' => 10,
			'key' => 'primary'
		],
		'jobtype' => [
			'type' => 'string',
			'null' => false,
			'length' => 45
		],
		'data' => [
			'type' => 'text',
			'null' => true,
			'default' => null
		],
		'group' => [
			'type' => 'string',
			'length' => 255,
			'null' => true,
			'default' => null
		],
		'reference' => [
			'type' => 'string',
			'length' => 255,
			'null' => true,
			'default' => null
		],
		'created' => [
			'type' => 'datetime',
			'null' => true,
			'default' => null
		],
		'notbefore' => [
			'type' => 'datetime',
			'null' => true,
			'default' => null
		],
		'fetched' => [
			'type' => 'datetime',
			'null' => true,
			'default' => null
		],
		'progress' => [
			'type' => 'float',
			'null' => true,
			'default' => null,
			'length' => '3,2'
		],
		'completed' => [
			'type' => 'datetime',
			'null' => true,
			'default' => null
		],
		'failed' => [
			'type' => 'integer',
			'null' => false,
			'default' => '0',
			'length' => 3
		],
		'failure_message' => [
			'type' => 'text',
			'null' => true,
			'default' => null
		],
		'workerkey' => [
			'type' => 'string',
			'null' => true,
			'length' => 45
		],
		'indexes' => [
			'PRIMARY' => [
				'column' => 'id',
				'unique' => 1
			]
		]
	];

}

