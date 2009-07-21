<?php

/**
 * @author MGriesbach@gmail.com
 * @package QueuePlugin
 * @subpackage QueuePlugin.Tests.Fixtures
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */

class QueuedTaskFixture extends CakeTestFixture {
	public $name = 'QueuedTask';
	public $table = 'queued_tasks';
	public $fields = array(
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
		'created' => array(
			'type' => 'datetime',
			'null' => false
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
		'indexes' => array(
			'PRIMARY' => array(
				'column' => 'id',
				'unique' => 1
			)
		)
	);
	public $records = array();

}
?>