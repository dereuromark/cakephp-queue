<?php

/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */
namespace Queue\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class QueuedTasksFixture extends TestFixture {

	public $fields = [
		'id' => ['type' => 'integer', 'null' => false, 'default' => null, 'length' => 10],
		'jobtype' => ['type' => 'string', 'null' => false, 'length' => 45],
		'data' => ['type' => 'text', 'null' => true, 'default' => null],
		'group' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null],
		'reference' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null],
		'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'notbefore' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'fetched' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'completed' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'progress' => ['type' => 'float', 'null' => true, 'default' => null],
		'failed' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => 3],
		'failure_message' => ['type' => 'text', 'null' => true, 'default' => null],
		'workerkey' => ['type' => 'string', 'null' => true, 'length' => 45],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	];

	public $records = [];

}
