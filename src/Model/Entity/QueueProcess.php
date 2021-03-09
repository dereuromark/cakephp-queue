<?php

namespace Queue\Model\Entity;

use Cake\ORM\Entity;

/**
 * QueueProcess Entity
 *
 * @property int $id
 * @property string $pid
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 * @property bool $terminate
 * @property string|null $server
 * @property string $workerkey
 */
class QueueProcess extends Entity {

	/**
	 * @var array
	 */
	protected $_accessible = [
		'*' => true,
		'id' => false,
	];

}
