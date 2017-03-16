<?php

namespace Queue\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $job_type
 * @property string $data
 * @property string $job_group
 * @property string $reference
 * @property \Cake\I18n\Time $created
 * @property \Cake\I18n\Time $notbefore
 * @property \Cake\I18n\Time $fetched
 * @property \Cake\I18n\Time $completed
 * @property float $progress
 * @property int $failed
 * @property string $failure_message
 * @property string $workerkey
 * @property string $status
 * @property int $priority
 */
class QueuedJob extends Entity {
}
