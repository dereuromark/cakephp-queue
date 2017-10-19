<?php
namespace Queue\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * QueueProcessesFixture
 */
class QueueProcessesFixture extends TestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	// @codingStandardsIgnoreStart
	public $fields = [
		'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
		'pid' => ['type' => 'string', 'length' => 40, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
		'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
		'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
		'_constraints' => [
			'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
		],
	];

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = [
		[
			//'id' => 1,
			'pid' => '1234',
			'created' => '2017-08-15 10:20:50',
			'modified' => '2017-08-15 10:20:50'
		],
	];

}
