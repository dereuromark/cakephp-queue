<?php

namespace Queue\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $job_task
 * @property string|null $data
 * @property string|null $job_group
 * @property string|null $reference
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $notbefore
 * @property \Cake\I18n\DateTime|null $fetched
 * @property \Cake\I18n\DateTime|null $completed
 * @property float|null $progress
 * @property int $attempts
 * @property string|null $failure_message
 * @property string|null $workerkey
 * @property string|null $status
 * @property int $priority
 * @property \Queue\Model\Entity\QueueProcess $worker_process
 */
class QueuedJob extends Entity {

	/**
	 * @var array<string, bool>
	 */
	protected array $_accessible = [
		'*' => true,
		'id' => false,
	];

}
