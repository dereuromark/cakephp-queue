<?php

class QueueSchema extends CakeSchema {

	public $file = 'schema_1.php';

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

	public $cron_tasks = [
		'id' => ['type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary'],
		'jobtype' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 45, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'],
		'title' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 40, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'],
		'data' => ['type' => 'text', 'null' => true, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'],
		'name' => ['type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => 'task / method', 'charset' => 'utf8'],
		'created' => ['type' => 'datetime', 'null' => false, 'default' => null],
		'notbefore' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'fetched' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'completed' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'failed' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => 3],
		'failure_message' => ['type' => 'text', 'null' => true, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'],
		'workerkey' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 45, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'],
		'interval' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10, 'comment' => 'in minutes'],
		'status' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => 2],
		'indexes' => [
			'PRIMARY' => ['column' => 'id', 'unique' => 1]
		],
		'tableParameters' => ['charset' => 'utf8', 'collate' => 'utf8_unicode_ci']
	];

	public $queued_tasks = [
		'id' => ['type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary'],
		'jobtype' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 45, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'],
		'data' => ['type' => 'text', 'null' => true, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'],
		'group' => ['type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'],
		'reference' => ['type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'],
		'created' => ['type' => 'datetime', 'null' => false, 'default' => null],
		'notbefore' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'fetched' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'progress' => ['type' => 'float', 'null' => true, 'default' => null, 'length' => '3,2'],
		'completed' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'failed' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => 3],
		'priority' => ['type' => 'integer', 'null' => false, 'default' => '5', 'length' => 4, 'key' => 'index'],
		'failure_message' => ['type' => 'text', 'null' => true, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'],
		'workerkey' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 45, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'],
		'indexes' => [
			'PRIMARY' => ['column' => 'id', 'unique' => 1],
			'priority' => ['column' => 'priority', 'unique' => 0]
		],
		'tableParameters' => ['charset' => 'utf8', 'collate' => 'utf8_unicode_ci']
	];

}
