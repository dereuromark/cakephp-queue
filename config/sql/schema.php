<?php

/**
 * @copyright esolut GmbH 2009
 * @link http://www.esolut.de
 * @author mgr2
 * @package QueuePlugin
 * @subpackage QueuePlugin.Schema
 * @version $Id:  $
 */
class QueueSchema extends CakeSchema {
	public $name = 'Queue';

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
		'created' => array(
			'type' => 'datetime', 
			'null' => false
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
}
?>