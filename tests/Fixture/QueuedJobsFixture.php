<?php
/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */

namespace Queue\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 */
class QueuedJobsFixture extends TestFixture {

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = [];

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer'],
		'job_type' => ['type' => 'string', 'length' => 45, 'null' => false, 'default' => null],
		'data' => ['type' => 'text', 'null' => true, 'default' => null],
		'priority' => ['type' => 'integer', 'length' => 3, 'null' => false, 'default' => 5],
		'job_group' => ['type' => 'string', 'length' => 255, 'null' => true],
		'reference' => ['type' => 'string', 'length' => 255, 'null' => true],
		'created' => ['type' => 'datetime', 'null' => true],
		'notbefore' => ['type' => 'datetime', 'null' => true],
		'fetched' => ['type' => 'datetime', 'null' => true],
		'completed' => ['type' => 'datetime', 'null' => true],
		'progress' => ['type' => 'float', 'null' => true],
		'status' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null],
		'failed' => ['type' => 'integer', 'length' => 3, 'null' => false, 'default' => 0],
		'failure_message' => ['type' => 'text', 'null' => true, 'default' => null],
		'workerkey' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
		'_constraints' => [
			'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
		],
	];

}
