<?php

namespace Queue\Model\Entity;

use Cake\ORM\Entity;

/**
 * QueueProcess Entity
 *
 * @property int $id
 * @property string $pid
 * @property int $active_job_id
 * @property string $arguments
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 * @property bool $terminate
 * @property string|null $server
 * @property string $workerkey
 * @property \Queue\Model\Entity\QueuedJob $active_job
 * @property \Queue\Model\Entity\QueuedJob[] $jobs
 */
class QueueProcess extends Entity {

	/**
	 * @var array<string, bool>
	 */
	protected $_accessible = [
		'*' => true,
		'id' => false,
	];

}
