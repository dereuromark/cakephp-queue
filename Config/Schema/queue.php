<?php

/**
 * @author MGriesbach@gmail.com
 * @package QueuePlugin
 * @subpackage QueuePlugin.Models
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */
class QueueSchema extends CakeSchema {

	public function before($event = array()) {
		return true;
	}

	public function after($event = array()) {
	}

	public $queued_tasks = array(
		'id' => array(
			'type' => 'integer',
			'null' => false,
			'default' => NULL,
			'length' => 10,
			'key' => 'primary'
		),
		'jobtype' => array(
			'type' => 'string',
			'null' => false,
			'length' => 45
		),
		'data' => array(
			'type' => 'text',
			'null' => true,
			'default' => NULL
		),
		'group' => array(
			'type' => 'string',
			'length' => 255,
			'null' => true,
			'default' => NULL
		),
		'reference' => array(
			'type' => 'string',
			'length' => 255,
			'null' => true,
			'default' => NULL
		),
		'created' => array(
			'type' => 'datetime',
			'null' => true,
			'default' => NULL
		),
		'notbefore' => array(
			'type' => 'datetime',
			'null' => true,
			'default' => NULL
		),
		'fetched' => array(
			'type' => 'datetime',
			'null' => true,
			'default' => NULL
		),
		'completed' => array(
			'type' => 'datetime',
			'null' => true,
			'default' => NULL
		),
		'failed' => array(
			'type' => 'integer',
			'null' => false,
			'default' => '0',
			'length' => 3
		),
		'failure_message' => array(
			'type' => 'text',
			'null' => true,
			'default' => NULL
		),
		'workerkey' => array(
			'type' => 'string',
			'null' => true,
			'length' => 45
		),
		'indexes' => array(
			'PRIMARY' => array(
				'column' => 'id',
				'unique' => 1
			)
		)
	);
}

