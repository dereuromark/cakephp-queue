<?php
/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */

namespace Queue\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class QueuedJobsFixture extends TestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
    // @codingStandardsIgnoreStart
    public array $fields = [
        'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'job_task' => ['type' => 'string', 'length' => 90, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'data' => ['type' => 'text', 'length' => 16777215, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'job_group' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'reference' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'notbefore' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'fetched' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'completed' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'progress' => ['type' => 'float', 'length' => null, 'precision' => null, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => ''],
        'attempts' => ['type' => 'integer', 'length' => 12, 'unsigned' => false, 'null' => false, 'default' => 0, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'failure_message' => ['type' => 'text', 'length' => 16777215, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'workerkey' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'status' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'priority' => ['type' => 'integer', 'length' => 3, 'unsigned' => false, 'null' => false, 'default' => 5, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
        ],
    ];
    // @codingStandardsIgnoreEnd

	/**
	 * Init method
	 *
	 * @return void
	 */
	public function init(): void {
		$this->records = [
		];
		parent::init();
	}

}
