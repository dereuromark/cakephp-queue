<?php

namespace Queue\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $job_type
 * @property string|null $data
 * @property string|null $job_group
 * @property string|null $reference
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime|null $notbefore
 * @property \Cake\I18n\FrozenTime|null $fetched
 * @property \Cake\I18n\FrozenTime|null $completed
 * @property float|null $progress
 * @property int $failed
 * @property string|null $failure_message
 * @property string|null $workerkey
 * @property string|null $status
 * @property int $priority
 * @property \Queue\Model\Entity\QueueProcess $worker_process
 */
class QueuedJob extends Entity {

	/**
	 * @var array
	 */
	protected $_accessible = [
		'*' => true,
		'id' => false,
	];

}
