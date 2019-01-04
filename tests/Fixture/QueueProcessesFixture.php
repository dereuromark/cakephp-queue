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
        'pid' => ['type' => 'string', 'length' => 40, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'terminate' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => '0', 'comment' => '', 'precision' => null],
        'server' => ['type' => 'string', 'length' => 90, 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
            'pid' => ['type' => 'unique', 'columns' => ['pid'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8_general_ci'
        ],
    ];
    // @codingStandardsIgnoreEnd

	/**
	 * Init method
	 *
	 * @return void
	 */
	public function init() {
		$this->records = [
			[
				'id' => 1,
				'pid' => 'Lorem ipsum dolor sit amet',
				'created' => '2019-01-04 11:01:50',
				'modified' => '2019-01-04 11:01:50',
				'terminate' => 1,
				'server' => 'Lorem ipsum dolor sit amet'
			],
		];
		parent::init();
	}

}
