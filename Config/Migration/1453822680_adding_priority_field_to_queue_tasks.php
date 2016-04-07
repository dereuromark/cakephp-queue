<?php
class AddingPriorityFieldToQueueTasks extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 */
	public $description = 'adding_priority_field_to_queue_tasks';

/**
 * Actions to be performed
 *
 * @var array $migration
 */
	public $migration = array(
		'up' => array(
            'create_field' => [
                'queued_tasks' => [
                    'priority' => [
                        'type' => 'integer',
                        'null' => false,
                        'default' => 5,
						'length' => 4
                    ],
					'indexes' => array(
						'priority' => array('column' => 'priority'),
					),
                ],
            ]
		),
		'down' => array(
            'drop_field' => [
                'queued_tasks' => [
                    'priority'
                ],
            ]
		),
	);

/**
 * Before migration callback
 *
 * @param string $direction Direction of migration process (up or down)
 * @return bool Should process continue
 */
	public function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction Direction of migration process (up or down)
 * @return bool Should process continue
 */
	public function after($direction) {
		return true;
	}
}
